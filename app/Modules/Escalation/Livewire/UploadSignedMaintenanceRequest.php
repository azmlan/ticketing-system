<?php

namespace App\Modules\Escalation\Livewire;

use App\Modules\Escalation\Services\SignedMaintenanceRequestService;
use App\Modules\Tickets\Exceptions\InvalidFileException;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Livewire\WithFileUploads;

class UploadSignedMaintenanceRequest extends Component
{
    use WithFileUploads;

    public string $ticketId = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $signedFile = null;

    public function mount(string $ticketId): void
    {
        if (! auth()->check()) {
            abort(403);
        }

        $this->ticketId = $ticketId;
    }

    public function upload(): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(401);
        }

        $uploadKey = 'upload:' . $user->id;
        $uploadMax = config('rate_limits.upload.max_attempts', 20);

        if (RateLimiter::tooManyAttempts($uploadKey, $uploadMax)) {
            abort(429);
        }

        $this->validate([
            'signedFile' => ['required', 'file', 'max:10240'],
        ]);

        try {
            app(SignedMaintenanceRequestService::class)->upload(
                $this->ticketId,
                $this->signedFile,
                $user
            );
        } catch (InvalidFileException $e) {
            $this->addError('signedFile', $e->getMessage());
            return;
        }

        RateLimiter::hit($uploadKey, config('rate_limits.upload.decay_seconds', 3600));

        $this->redirect(route('tickets.show', $this->ticketId), navigate: true);
    }

    public function render()
    {
        return view('livewire.escalation.upload-signed-maintenance-request');
    }
}
