<?php

namespace App\Modules\Tickets\Livewire;

use App\Modules\Admin\Models\CustomFieldOption;
use App\Modules\Admin\Models\CustomFieldValue;
use App\Modules\Assignment\Services\AssignmentService;
use App\Modules\Assignment\Services\TransferService;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Exceptions\InvalidTicketTransitionException;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Models\TransferRequest;
use App\Modules\Tickets\Services\TicketStateMachine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

    public function managerAssign(): void
    {
        $manager = auth()->user();
        $tech = User::findOrFail($this->assignToUserId);
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

    public function requestTransfer(): void
    {
        $from = auth()->user();
        $to = User::findOrFail($this->transferToUserId);
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

        $ticketSla = DB::table('ticket_sla')
            ->where('ticket_id', $this->ticket->id)
            ->first();

        $customFieldEntries = $this->buildCustomFieldDisplay();

        return view('livewire.tickets.show-ticket', compact(
            'techs', 'pendingTransfer', 'ticketSla', 'customFieldEntries'
        ));
    }

    private function buildCustomFieldDisplay(): Collection
    {
        $values = CustomFieldValue::with(['field' => fn ($q) => $q->withTrashed()])
            ->where('ticket_id', $this->ticket->id)
            ->get();

        // Collect all option IDs needed for dropdown / multi_select display
        $optionIds = $values
            ->filter(fn ($cfv) => $cfv->field && in_array($cfv->field->field_type, ['dropdown', 'multi_select']))
            ->flatMap(function ($cfv) {
                if ($cfv->field->field_type === 'multi_select') {
                    return json_decode($cfv->value, true) ?? [];
                }

                return [$cfv->value];
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $optionsMap = $optionIds
            ? CustomFieldOption::withTrashed()->whereIn('id', $optionIds)->get()->keyBy('id')
            : collect();

        return $values
            ->filter(fn ($cfv) => $cfv->field !== null)
            ->map(function ($cfv) use ($optionsMap) {
                $field = $cfv->field;

                $displayValue = match ($field->field_type) {
                    'checkbox' => $cfv->value === '1' ? __('common.yes') : __('common.no'),
                    'dropdown' => optional($optionsMap->get($cfv->value))->localizedValue() ?? $cfv->value,
                    'multi_select' => collect(json_decode($cfv->value, true) ?? [])
                        ->map(fn ($id) => optional($optionsMap->get($id))->localizedValue() ?? $id)
                        ->filter()
                        ->join(', '),
                    default => $cfv->value,
                };

                return [
                    'label' => $field->localizedName(),
                    'value' => $displayValue,
                    'inactive' => ! $field->is_active || $field->trashed(),
                ];
            });
    }
}
