<?php

namespace App\Modules\Tickets\Livewire;

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\CustomField;
use App\Modules\Admin\Models\CustomFieldValue;
use App\Modules\Admin\Models\Subcategory;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Events\TicketStatusChanged;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Services\FileUploadService;
use App\Modules\Tickets\Services\RichTextSanitizer;
use App\Modules\Tickets\Services\TicketCounterService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class CreateTicket extends Component
{
    use WithFileUploads;

    public string $subject = '';

    public string $description = '';

    public string $category_id = '';

    public string $subcategory_id = '';

    /** @var array<int, TemporaryUploadedFile> */
    public array $attachments = [];

    /** @var array<string, mixed> Custom field values keyed by custom_field.id */
    public array $customFieldValues = [];

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

        // Drop values for category-scoped fields that no longer apply
        $visibleIds = $this->applicableCustomFields()->pluck('id')->flip()->all();
        $this->customFieldValues = array_intersect_key($this->customFieldValues, $visibleIds);
    }

    public function submit(): void
    {
        $user = auth()->user();

        // Ticket create rate limit
        $createKey = 'ticket.create:'.$user->id;
        $createMax = config('rate_limits.ticket_create.max_attempts', 10);
        $createDecay = config('rate_limits.ticket_create.decay_seconds', 3600);

        if (RateLimiter::tooManyAttempts($createKey, $createMax)) {
            abort(429);
        }

        // Upload rate limit (pre-flight before ticket creation)
        if (! empty($this->attachments)) {
            $uploadKey = 'upload:'.$user->id;
            $uploadMax = config('rate_limits.upload.max_attempts', 20);

            if (RateLimiter::tooManyAttempts($uploadKey, $uploadMax)) {
                abort(429);
            }
        }

        $this->validate($this->rules());

        $category = Category::find($this->category_id);
        $description = app(RichTextSanitizer::class)->sanitize($this->description);
        $displayNumber = app(TicketCounterService::class)->generate();

        $ticket = Ticket::create([
            'display_number' => $displayNumber,
            'subject' => $this->subject,
            'description' => $description,
            'status' => TicketStatus::AwaitingAssignment,
            'category_id' => $category->id,
            'subcategory_id' => $this->subcategory_id ?: null,
            'group_id' => $category->group_id,
            'requester_id' => $user->id,
            'incident_origin' => 'web',
        ]);

        RateLimiter::hit($createKey, $createDecay);

        TicketStatusChanged::dispatch($ticket, '', 'awaiting_assignment', $user);

        $this->saveCustomFieldValues($ticket);

        if (! empty($this->attachments)) {
            $uploadKey = 'upload:'.$user->id;
            $uploadDecay = config('rate_limits.upload.decay_seconds', 3600);
            $uploadMax = config('rate_limits.upload.max_attempts', 20);
            $service = app(FileUploadService::class);

            foreach ($this->attachments as $file) {
                $service->store($file, $ticket, $user);
                RateLimiter::hit($uploadKey, $uploadDecay);
            }
        }

        $this->redirect(route('tickets.show', $ticket->id), navigate: true);
    }

    private function saveCustomFieldValues(Ticket $ticket): void
    {
        foreach ($this->applicableCustomFields() as $field) {
            $rawValue = $this->customFieldValues[$field->id] ?? null;

            if ($field->field_type === 'checkbox') {
                $value = ($rawValue ? '1' : '0');
            } elseif ($field->field_type === 'multi_select') {
                if (empty($rawValue) || ! is_array($rawValue)) {
                    continue;
                }
                $value = json_encode(array_values(array_filter($rawValue)));
            } else {
                if ($rawValue === null || $rawValue === '') {
                    continue;
                }
                $value = (string) $rawValue;
            }

            CustomFieldValue::create([
                'ticket_id' => $ticket->id,
                'custom_field_id' => $field->id,
                'value' => $value,
            ]);
        }
    }

    private function rules(): array
    {
        $rules = [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'attachments' => ['array', 'max:5'],
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

        foreach ($this->applicableCustomFields() as $field) {
            $key = 'customFieldValues.'.$field->id;

            if ($field->field_type === 'checkbox') {
                $rules[$key] = ['boolean'];

                continue;
            }

            if ($field->field_type === 'multi_select') {
                $rules[$key] = $field->is_required
                    ? ['required', 'array', 'min:1']
                    : ['nullable', 'array'];

                $validOptionIds = $field->options
                    ->filter(fn ($o) => $o->is_active && $o->deleted_at === null)
                    ->pluck('id')
                    ->all();
                $rules[$key.'.*'] = [Rule::in($validOptionIds)];

                continue;
            }

            $fieldRules = $field->is_required
                ? ['required', 'string']
                : ['nullable', 'string'];

            if ($field->field_type === 'number') {
                $fieldRules[] = 'numeric';
            } elseif ($field->field_type === 'date') {
                $fieldRules[] = 'date';
            } elseif ($field->field_type === 'dropdown') {
                $validOptionIds = $field->options
                    ->filter(fn ($o) => $o->is_active && $o->deleted_at === null)
                    ->pluck('id')
                    ->all();
                $fieldRules[] = Rule::in($validOptionIds);
            }

            $rules[$key] = $fieldRules;
        }

        return $rules;
    }

    private function applicableCustomFields(): \Illuminate\Database\Eloquent\Collection
    {
        return CustomField::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->where('scope_type', 'global');
                if ($this->category_id) {
                    $q->orWhere(function ($q2) {
                        $q2->where('scope_type', 'category')
                            ->where('scope_category_id', $this->category_id);
                    });
                }
            })
            ->with(['options' => fn ($q) => $q->whereNull('deleted_at')->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('display_order')
            ->get();
    }

    public function render()
    {
        return view('livewire.tickets.create-ticket', [
            'categories' => Category::active()->orderBy('sort_order')->get(),
            'customFields' => $this->applicableCustomFields(),
        ]);
    }
}
