<?php

namespace App\Modules\Tickets\Livewire;

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Subcategory;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Services\RichTextSanitizer;
use App\Modules\Tickets\Services\TicketCounterService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CreateTicket extends Component
{
    public string $subject = '';
    public string $description = '';
    public string $category_id = '';
    public string $subcategory_id = '';

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
        $key   = 'ticket.create:' . auth()->id();
        $max   = config('rate_limits.ticket_create.max_attempts', 10);
        $decay = config('rate_limits.ticket_create.decay_seconds', 3600);

        if (RateLimiter::tooManyAttempts($key, $max)) {
            abort(429);
        }

        $this->validate($this->rules());

        $category = Category::find($this->category_id);

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
            'requester_id'    => auth()->id(),
            'incident_origin' => 'web',
        ]);

        RateLimiter::hit($key, $decay);

        $this->redirect(route('tickets.show', $ticket->id), navigate: true);
    }

    private function rules(): array
    {
        $rules = [
            'subject'     => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
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
