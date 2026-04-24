<?php

namespace App\Modules\Tickets\Livewire;

use App\Modules\Admin\Models\Category;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ManagerDashboard extends Component
{
    public function mount(): void
    {
        $user = auth()->user();
        abort_unless(
            $user->is_super_user || $user->hasPermission('ticket.view-all'),
            403
        );
    }

    public function render()
    {
        return view('livewire.tickets.manager-dashboard', [
            'statusCounts'     => $this->computeStatusCounts(),
            'categoryCounts'   => $this->computeCategoryCounts(),
            'createdWeek'      => $this->countCreatedSince(now()->startOfWeek()),
            'createdMonth'     => $this->countCreatedSince(now()->startOfMonth()),
            'avgResolutionHrs' => $this->computeAvgResolutionHours(),
            'slaCompliance'    => $this->computeSlaCompliance(),
            'breachedCount'    => $this->countBreached(),
            'breachedTickets'  => $this->fetchBreachedTickets(),
            'escalationQueue'  => $this->fetchEscalationQueue(),
            'unassignedCount'  => $this->countUnassigned(),
            'teamWorkload'     => $this->fetchTeamWorkload(),
            'recentActivity'   => $this->fetchRecentActivity(),
        ]);
    }

    // ── Summary stats ─────────────────────────────────────────────────────────

    private function computeStatusCounts(): array
    {
        $rows = DB::table('tickets')
            ->whereNull('deleted_at')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $statuses = [
            'awaiting_assignment', 'in_progress', 'on_hold',
            'awaiting_approval', 'action_required', 'awaiting_final_approval',
            'resolved', 'closed', 'cancelled',
        ];

        return $rows + array_fill_keys($statuses, 0);
    }

    private function computeCategoryCounts(): \Illuminate\Support\Collection
    {
        return DB::table('tickets')
            ->join('categories', 'tickets.category_id', '=', 'categories.id')
            ->whereNull('tickets.deleted_at')
            ->select('categories.name_en', 'categories.name_ar', DB::raw('COUNT(*) as total'))
            ->groupBy('categories.id', 'categories.name_en', 'categories.name_ar')
            ->orderByDesc('total')
            ->get();
    }

    private function countCreatedSince(\Carbon\Carbon $since): int
    {
        return DB::table('tickets')
            ->whereNull('deleted_at')
            ->where('created_at', '>=', $since)
            ->count();
    }

    private function computeAvgResolutionHours(): float
    {
        $avg = DB::table('ticket_sla')
            ->join('tickets', 'ticket_sla.ticket_id', '=', 'tickets.id')
            ->whereNull('tickets.deleted_at')
            ->whereIn('tickets.status', ['resolved', 'closed'])
            ->where('ticket_sla.resolution_elapsed_minutes', '>', 0)
            ->avg('ticket_sla.resolution_elapsed_minutes');

        return $avg ? round($avg / 60, 1) : 0.0;
    }

    // ── SLA section ───────────────────────────────────────────────────────────

    private function computeSlaCompliance(): int
    {
        $total = DB::table('ticket_sla')
            ->join('tickets', 'ticket_sla.ticket_id', '=', 'tickets.id')
            ->whereNull('tickets.deleted_at')
            ->whereIn('tickets.status', ['resolved', 'closed'])
            ->count();

        if ($total === 0) {
            return 0;
        }

        $compliant = DB::table('ticket_sla')
            ->join('tickets', 'ticket_sla.ticket_id', '=', 'tickets.id')
            ->whereNull('tickets.deleted_at')
            ->whereIn('tickets.status', ['resolved', 'closed'])
            ->where('ticket_sla.resolution_status', 'on_track')
            ->count();

        return (int) round(($compliant / $total) * 100);
    }

    private function countBreached(): int
    {
        return DB::table('ticket_sla')
            ->join('tickets', 'ticket_sla.ticket_id', '=', 'tickets.id')
            ->whereNull('tickets.deleted_at')
            ->whereNotIn('tickets.status', ['resolved', 'closed', 'cancelled'])
            ->where('ticket_sla.resolution_status', 'breached')
            ->count();
    }

    private function fetchBreachedTickets(): \Illuminate\Support\Collection
    {
        return DB::table('ticket_sla')
            ->join('tickets', 'ticket_sla.ticket_id', '=', 'tickets.id')
            ->leftJoin('users', 'tickets.assigned_to', '=', 'users.id')
            ->whereNull('tickets.deleted_at')
            ->whereNotIn('tickets.status', ['resolved', 'closed', 'cancelled'])
            ->where('ticket_sla.resolution_status', 'breached')
            ->select(
                'tickets.id',
                'tickets.display_number',
                'tickets.subject',
                'tickets.status',
                'users.full_name as tech_name',
                DB::raw('ROUND(ticket_sla.resolution_elapsed_minutes / 60, 1) as overdue_hours'),
            )
            ->orderByDesc('ticket_sla.resolution_elapsed_minutes')
            ->get();
    }

    // ── Escalation queue ──────────────────────────────────────────────────────

    private function fetchEscalationQueue(): \Illuminate\Support\Collection
    {
        return DB::table('tickets')
            ->whereNull('deleted_at')
            ->whereIn('status', ['awaiting_approval', 'awaiting_final_approval'])
            ->select('id', 'display_number', 'subject', 'status', 'created_at')
            ->orderBy('created_at')
            ->get();
    }

    // ── Unassigned count ──────────────────────────────────────────────────────

    private function countUnassigned(): int
    {
        return DB::table('tickets')
            ->whereNull('deleted_at')
            ->whereNull('assigned_to')
            ->whereNotIn('status', ['resolved', 'closed', 'cancelled'])
            ->count();
    }

    // ── Team workload ─────────────────────────────────────────────────────────

    private function fetchTeamWorkload(): \Illuminate\Support\Collection
    {
        $openStatuses = [
            'awaiting_assignment', 'in_progress', 'on_hold',
            'awaiting_approval', 'action_required', 'awaiting_final_approval',
        ];

        return DB::table('tickets')
            ->join('users', 'tickets.assigned_to', '=', 'users.id')
            ->whereNull('tickets.deleted_at')
            ->whereIn('tickets.status', $openStatuses)
            ->whereNotNull('tickets.assigned_to')
            ->select('users.id', 'users.full_name', DB::raw('COUNT(*) as open_count'))
            ->groupBy('users.id', 'users.full_name')
            ->orderByDesc('open_count')
            ->get();
    }

    // ── Recent activity feed ──────────────────────────────────────────────────

    private function fetchRecentActivity(): \Illuminate\Support\Collection
    {
        // Proxy for audit feed until Phase 10 audit_logs table is built:
        // show the 20 most recently updated tickets system-wide.
        return DB::table('tickets')
            ->leftJoin('users as requester', 'tickets.requester_id', '=', 'requester.id')
            ->leftJoin('users as tech', 'tickets.assigned_to', '=', 'tech.id')
            ->whereNull('tickets.deleted_at')
            ->select(
                'tickets.id',
                'tickets.display_number',
                'tickets.subject',
                'tickets.status',
                'tickets.updated_at',
                'requester.full_name as requester_name',
                'tech.full_name as tech_name',
            )
            ->orderByDesc('tickets.updated_at')
            ->limit(20)
            ->get();
    }
}
