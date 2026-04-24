<?php

namespace App\Modules\Escalation\Livewire;

use App\Modules\Escalation\Models\MaintenanceRequest;
use App\Modules\Escalation\Services\MaintenanceRequestApprovalService;
use App\Modules\Tickets\Livewire\ShowTicket;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ReviewSignedMaintenanceRequest extends Component
{
    public string $ticketId           = '';
    public string $maintenanceRequestId = '';
    public string $reviewNotes        = '';
    public string $closeReason        = '';
    public string $closeReasonText    = '';
    public bool   $showRejectForm     = false;
    public bool   $showPermanentForm  = false;

    public function mount(string $ticketId): void
    {
        $user = auth()->user();

        if (! $user || (! $user->is_super_user && ! $user->hasPermission('escalation.approve'))) {
            abort(403);
        }

        $mr = MaintenanceRequest::where('ticket_id', $ticketId)->firstOrFail();

        $this->ticketId             = $ticketId;
        $this->maintenanceRequestId = $mr->id;
    }

    public function approve(): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(401);
        }

        $mr = MaintenanceRequest::findOrFail($this->maintenanceRequestId);

        app(MaintenanceRequestApprovalService::class)->approve($mr, $user);

        $this->redirect(route('tickets.show', $this->ticketId), navigate: true);
    }

    public function rejectResubmit(): void
    {
        $this->validate([
            'reviewNotes' => ['required', 'string', 'max:1000'],
        ]);

        $user = auth()->user();

        if (! $user) {
            abort(401);
        }

        $mr = MaintenanceRequest::findOrFail($this->maintenanceRequestId);

        app(MaintenanceRequestApprovalService::class)->rejectResubmit($mr, $user, $this->reviewNotes);

        $this->redirect(route('tickets.show', $this->ticketId), navigate: true);
    }

    public function rejectPermanently(): void
    {
        $rules = [
            'closeReason' => ['required', Rule::in(ShowTicket::CLOSE_REASONS)],
        ];

        if ($this->closeReason === 'other') {
            $rules['closeReasonText'] = ['required', 'string', 'max:1000'];
        } else {
            $rules['closeReasonText'] = ['nullable'];
        }

        $this->validate($rules);

        $user = auth()->user();

        if (! $user) {
            abort(401);
        }

        $mr = MaintenanceRequest::findOrFail($this->maintenanceRequestId);

        app(MaintenanceRequestApprovalService::class)->rejectPermanently(
            $mr,
            $user,
            $this->closeReason,
            $this->closeReasonText ?: null
        );

        $this->redirect(route('tickets.show', $this->ticketId), navigate: true);
    }

    public function render()
    {
        $mr = MaintenanceRequest::with('reviewer')->findOrFail($this->maintenanceRequestId);

        return view('livewire.escalation.review-signed-maintenance-request', [
            'mr'           => $mr,
            'closeReasons' => ShowTicket::CLOSE_REASONS,
        ]);
    }
}
