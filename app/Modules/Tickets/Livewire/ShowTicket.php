<?php

namespace App\Modules\Tickets\Livewire;

use App\Modules\Assignment\Services\AssignmentService;
use App\Modules\Assignment\Services\TransferService;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Exceptions\InvalidTicketTransitionException;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Models\TransferRequest;
use App\Modules\Tickets\Services\TicketStateMachine;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ShowTicket extends Component
{
    public const CLOSE_REASONS = [
        'duplicate',
        'not_it',
        'cannot_reproduce',
        'out_of_scope',
        'requester_unresponsive',
        'resolved_externally',
        'other',
    ];

    public Ticket $ticket;

    public string $assignToUserId = '';

    public string $transferToUserId = '';

    public string $closeReason = '';

    public string $closeReasonText = '';

    public function mount(Ticket $ticket): void
    {
        $this->authorize('view', $ticket);

        $this->ticket = $ticket->loadMissing([
            'requester', 'assignedTo', 'group', 'category', 'subcategory', 'attachments',
        ]);
    }

    public function selfAssign(): void
    {
        $user = auth()->user();
        app(AssignmentService::class)->selfAssign($this->ticket, $user);
        $this->ticket->refresh();
    }

    public function managerAssign(string $techId): void
    {
        $manager = auth()->user();
        $tech = User::findOrFail($techId);
        app(AssignmentService::class)->managerAssign($this->ticket, $manager, $tech);
        $this->ticket->refresh();
        $this->assignToUserId = '';
    }

    public function hold(): void
    {
        $user = auth()->user();
        app(TicketStateMachine::class)->transition($this->ticket, 'on_hold', $user);
        $this->ticket->refresh();
    }

    public function resume(): void
    {
        $user = auth()->user();
        app(TicketStateMachine::class)->transition($this->ticket, 'in_progress', $user);
        $this->ticket->refresh();
    }

    public function close(): void
    {
        $this->authorize('close', $this->ticket);

        $rules = [
            'closeReason' => ['required', Rule::in(self::CLOSE_REASONS)],
        ];

        if ($this->closeReason === 'other') {
            $rules['closeReasonText'] = ['required', 'string', 'max:1000'];
        } else {
            $rules['closeReasonText'] = ['nullable'];
        }

        $this->validate($rules);

        $this->ticket->close_reason = $this->closeReason;
        $this->ticket->close_reason_text = $this->closeReason === 'other' ? $this->closeReasonText : null;

        try {
            app(TicketStateMachine::class)->transition($this->ticket, 'closed', auth()->user());
        } catch (InvalidTicketTransitionException $e) {
            $this->ticket->close_reason = null;
            $this->ticket->close_reason_text = null;
            $this->addError('closeReason', __('tickets.validation.invalid_transition'));
            return;
        }

        $this->ticket->refresh();
        $this->closeReason = '';
        $this->closeReasonText = '';
    }

    public function cancel(): void
    {
        $this->authorize('cancel', $this->ticket);

        app(TicketStateMachine::class)->transition($this->ticket, 'cancelled', auth()->user());

        $this->ticket->refresh();
    }

    public function requestTransfer(string $toUserId): void
    {
        $from = auth()->user();
        $to = User::findOrFail($toUserId);
        app(TransferService::class)->request($this->ticket, $from, $to);
        $this->ticket->refresh();
        $this->transferToUserId = '';
    }

    public function acceptTransfer(string $transferId): void
    {
        $actor = auth()->user();
        $tr = TransferRequest::findOrFail($transferId);
        app(TransferService::class)->accept($tr, $actor);
        $this->ticket->refresh();
    }

    public function rejectTransfer(string $transferId): void
    {
        $actor = auth()->user();
        $tr = TransferRequest::findOrFail($transferId);
        app(TransferService::class)->reject($tr, $actor);
        $this->ticket->refresh();
    }

    public function revokeTransfer(string $transferId): void
    {
        $actor = auth()->user();
        $tr = TransferRequest::findOrFail($transferId);
        app(TransferService::class)->revoke($tr, $actor);
        $this->ticket->refresh();
    }

    public function render()
    {
        $techs = User::where('is_tech', true)
            ->where('id', '!=', auth()->id())
            ->orderBy('full_name')
            ->get();

        $pendingTransfer = TransferRequest::where('ticket_id', $this->ticket->id)
            ->where('status', 'pending')
            ->first();

        return view('livewire.tickets.show-ticket', compact('techs', 'pendingTransfer'));
    }
}
