<?php

namespace App\Modules\Tickets\Livewire;

use App\Modules\Assignment\Services\TransferService;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Models\TransferRequest;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class TechDashboard extends Component
{
    // Group filter state — persistence wired up in Task 6.4
    public array $selectedGroupIds = [];

    private const OPEN_STATUSES = [
        'awaiting_assignment',
        'in_progress',
        'on_hold',
        'awaiting_approval',
        'action_required',
        'awaiting_final_approval',
    ];

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless(
            $user->is_tech || $user->is_super_user || $user->hasPermission('ticket.view-assigned'),
            403
        );
    }

    public function acceptTransfer(string $transferId): void
    {
        $user = auth()->user();
        $tr = TransferRequest::findOrFail($transferId);
        app(TransferService::class)->accept($tr, $user);
    }

    public function declineTransfer(string $transferId): void
    {
        $user = auth()->user();
        $tr = TransferRequest::findOrFail($transferId);
        app(TransferService::class)->reject($tr, $user);
    }

    public function render()
    {
        $user = auth()->user();

        $groupIds = DB::table('group_user')
            ->where('user_id', $user->id)
            ->pluck('group_id')
            ->all();

        $queueTickets = $this->buildQueueQuery($groupIds)->with(['category'])->get();
        $myTickets    = $this->buildMyTicketsQuery($user->id)->with(['category'])->get();

        $allTicketIds = $queueTickets->pluck('id')->merge($myTickets->pluck('id'))->unique()->all();
        $slaMap = DB::table('ticket_sla')
            ->whereIn('ticket_id', $allTicketIds)
            ->get()
            ->keyBy('ticket_id');

        $pendingTransfers = TransferRequest::where('to_user_id', $user->id)
            ->where('status', 'pending')
            ->with(['ticket', 'fromUser'])
            ->get();

        $stats = $this->computeStats($user->id);

        return view('livewire.tickets.tech-dashboard', compact(
            'queueTickets',
            'myTickets',
            'slaMap',
            'pendingTransfers',
            'stats'
        ));
    }

    private function buildQueueQuery(array $groupIds)
    {
        if (empty($groupIds)) {
            return Ticket::query()->whereRaw('0=1');
        }

        return Ticket::query()
            ->whereIn('group_id', $groupIds)
            ->whereNull('assigned_to')
            ->whereIn('status', self::OPEN_STATUSES)
            ->orderByRaw("CASE priority
                WHEN 'critical' THEN 0
                WHEN 'high' THEN 1
                WHEN 'medium' THEN 2
                ELSE 3
            END")
            ->orderBy('created_at');
    }

    private function buildMyTicketsQuery(string $userId)
    {
        return Ticket::query()
            ->where('assigned_to', $userId)
            ->whereIn('status', self::OPEN_STATUSES)
            ->leftJoin('ticket_sla', 'tickets.id', '=', 'ticket_sla.ticket_id')
            ->select('tickets.*')
            ->orderByRaw("CASE
                WHEN ticket_sla.resolution_status = 'breached' OR ticket_sla.response_status = 'breached' THEN 0
                WHEN ticket_sla.resolution_status = 'warning' OR ticket_sla.response_status = 'warning' THEN 1
                WHEN ticket_sla.resolution_status IS NOT NULL THEN 2
                ELSE 3
            END")
            ->orderByRaw("CASE priority
                WHEN 'critical' THEN 0
                WHEN 'high' THEN 1
                WHEN 'medium' THEN 2
                ELSE 3
            END")
            ->orderBy('tickets.created_at');
    }

    private function computeStats(string $userId): array
    {
        $open = Ticket::query()
            ->where('assigned_to', $userId)
            ->whereIn('status', self::OPEN_STATUSES)
            ->count();

        $resolvedWeek = Ticket::query()
            ->where('assigned_to', $userId)
            ->where('status', 'resolved')
            ->where('resolved_at', '>=', now()->startOfWeek())
            ->count();

        $resolvedMonth = Ticket::query()
            ->where('assigned_to', $userId)
            ->where('status', 'resolved')
            ->where('resolved_at', '>=', now()->startOfMonth())
            ->count();

        $closedTotal = DB::table('ticket_sla')
            ->join('tickets', 'ticket_sla.ticket_id', '=', 'tickets.id')
            ->where('tickets.assigned_to', $userId)
            ->whereIn('tickets.status', ['resolved', 'closed'])
            ->count();

        $compliant = DB::table('ticket_sla')
            ->join('tickets', 'ticket_sla.ticket_id', '=', 'tickets.id')
            ->where('tickets.assigned_to', $userId)
            ->whereIn('tickets.status', ['resolved', 'closed'])
            ->where('ticket_sla.resolution_status', 'on_track')
            ->count();

        return [
            'open'           => $open,
            'resolved_week'  => $resolvedWeek,
            'resolved_month' => $resolvedMonth,
            'sla_compliance' => $closedTotal > 0 ? (int) round(($compliant / $closedTotal) * 100) : 0,
        ];
    }
}
