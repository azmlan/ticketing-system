<?php

namespace App\Modules\Tickets\Livewire;

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Subcategory;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Events\TicketStatusChanged;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Services\FileUploadService;
use App\Modules\Tickets\Services\RichTextSanitizer;
use App\Modules\Tickets\Services\TicketCounterService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class CreateTicket extends Component
{
    use WithFileUploads;

    public string $subject = '';
    public string $description = '';
    public string $category_id = '';
    public string $subcategory_id = '';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $attachments = [];

    public Collection $subcategories;

    public function mount(): void
    {
        $this->subcategories = collect();
    }

    public function updatedCategoryId(): void
    {
        $this->subcategory_id = '';
        $this->subcategories = $this->category_id
            ? Subcategory::where('category_id', $this->category_id)->active()->orderBy('sort_order')->get()
            : collect();
    }

    public function submit(): void
    {
        $user = auth()->user();

        // Ticket create rate limit
        $createKey   = 'ticket.create:' . $user->id;
        $createMax   = config('rate_limits.ticket_create.max_attempts', 10);
        $createDecay = config('rate_limits.ticket_create.decay_seconds', 3600);

        if (RateLimiter::tooManyAttempts($createKey, $createMax)) {
            abort(429);
        }

        // Upload rate limit (pre-flight before ticket creation)
        if (! empty($this->attachments)) {
            $uploadKey   = 'upload:' . $user->id;
            $uploadMax   = config('rate_limits.upload.max_attempts', 20);

            if (RateLimiter::tooManyAttempts($uploadKey, $uploadMax)) {
                abort(429);
            }
        }

        $this->validate($this->rules());

        $category    = Category::find($this->category_id);
        $description = app(RichTextSanitizer::class)->sanitize($this->description);
        $displayNumber = app(TicketCounterService::class)->generate();

        $ticket = Ticket::create([
            'display_number'  => $displayNumber,
            'subject'         => $this->subject,
            'description'     => $description,
            'status'          => TicketStatus::AwaitingAssignment,
            'category_id'     => $category->id,
            'subcategory_id'  => $this->subcategory_id ?: null,
            'group_id'        => $category->group_id,
            'requester_id'    => $user->id,
            'incident_origin' => 'web',
        ]);

        RateLimiter::hit($createKey, $createDecay);

        TicketStatusChanged::dispatch($ticket, '', 'awaiting_assignment', $user);

        if (! empty($this->attachments)) {
            $uploadKey   = 'upload:' . $user->id;
            $uploadDecay = config('rate_limits.upload.decay_seconds', 3600);
            $uploadMax   = config('rate_limits.upload.max_attempts', 20);
            $service     = app(FileUploadService::class);

            foreach ($this->attachments as $file) {
                $service->store($file, $ticket, $user);
                RateLimiter::hit($uploadKey, $uploadDecay);
            }
        }

        $this->redirect(route('tickets.show', $ticket->id), navigate: true);
    }

    private function rules(): array
    {
        $rules = [
            'subject'       => ['required', 'string', 'max:255'],
            'description'   => ['required', 'string'],
            'category_id'   => ['required', 'exists:categories,id'],
            'attachments'   => ['array', 'max:5'],
            'attachments.*' => ['file'],
        ];

        $rules['subcategory_id'] = [
            function ($attr, $value, $fail) {
                if (! $this->category_id) {
                    return;
                }

                $hasRequired = Subcategory::where('category_id', $this->category_id)
                    ->where('is_required', true)
                    ->active()
                    ->exists();

                if ($hasRequired && ! $value) {
                    $fail(__('tickets.validation.subcategory_required'));
                    return;
                }

                if ($value && ! Subcategory::where('id', $value)
                    ->where('category_id', $this->category_id)
                    ->active()
                    ->exists()) {
                    $fail(__('tickets.validation.subcategory_invalid'));
                }
            },
        ];

        return $rules;
    }

    public function render()
    {
        return view('livewire.tickets.create-ticket', [
            'categories' => Category::active()->orderBy('sort_order')->get(),
        ]);
    }
}
