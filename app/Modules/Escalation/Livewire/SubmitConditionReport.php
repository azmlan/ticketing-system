<?php

namespace App\Modules\Escalation\Livewire;

use App\Modules\Admin\Models\Location;
use App\Modules\Escalation\Services\ConditionReportService;
use App\Modules\Tickets\Exceptions\InvalidFileException;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Livewire\WithFileUploads;

class SubmitConditionReport extends Component
{
    use WithFileUploads;

    public string $ticketId = '';
    public string $reportType = '';
    public string $locationId = '';
    public string $currentCondition = '';
    public string $conditionAnalysis = '';
    public string $requiredAction = '';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $attachments = [];

    public function mount(string $ticketId): void
    {
        $user = auth()->user();

        if (! $user || ! $user->is_tech) {
            abort(403);
        }

        $this->ticketId = $ticketId;
    }

    public function submit(): void
    {
        $user = auth()->user();

        if (! $user || ! $user->is_tech) {
            abort(403);
        }

        $this->validate($this->rules());

        if (! empty($this->attachments)) {
            $uploadKey = 'upload:' . $user->id;
            $uploadMax = config('rate_limits.upload.max_attempts', 20);

            if (RateLimiter::tooManyAttempts($uploadKey, $uploadMax)) {
                abort(429);
            }
        }

        try {
            app(ConditionReportService::class)->submit(
                ticketId: $this->ticketId,
                data: [
                    'reportType'        => $this->reportType,
                    'locationId'        => $this->locationId,
                    'currentCondition'  => $this->currentCondition,
                    'conditionAnalysis' => $this->conditionAnalysis,
                    'requiredAction'    => $this->requiredAction,
                ],
                files: $this->attachments,
                actor: $user,
            );
        } catch (InvalidFileException $e) {
            $this->addError('attachments', $e->getMessage());
            return;
        }

        if (! empty($this->attachments)) {
            $uploadKey   = 'upload:' . $user->id;
            $uploadDecay = config('rate_limits.upload.decay_seconds', 3600);

            foreach ($this->attachments as $_) {
                RateLimiter::hit($uploadKey, $uploadDecay);
            }
        }

        $this->redirect(route('tickets.show', $this->ticketId), navigate: true);
    }

    private function rules(): array
    {
        return [
            'reportType'        => ['required', 'string', 'max:255'],
            'locationId'        => ['nullable', 'string'],
            'currentCondition'  => ['required', 'string'],
            'conditionAnalysis' => ['required', 'string'],
            'requiredAction'    => ['required', 'string'],
            'attachments'       => ['array', 'max:5'],
            'attachments.*'     => ['file'],
        ];
    }

    public function render()
    {
        $locations = Location::active()->whereNull('deleted_at')->orderBy('sort_order')->get();

        return view('livewire.escalation.submit-condition-report', compact('locations'));
    }
}
