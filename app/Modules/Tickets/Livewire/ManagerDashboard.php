<?php

namespace App\Modules\Tickets\Livewire;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ManagerDashboard extends Component
{
    use WithPagination;

    public string $sortBy = 'updated_at';

    public string $sortDir = 'desc';

    private const ALLOWED_SORTS = ['created_at', 'priority', 'updated_at'];

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless(
            $user->is_super_user || $user->hasPermission('ticket.view-all'),
            403
        );
    }

    public function updatedSortBy(): void
    {
        $this->resetPage('activityPage');
    }

    public function updatedSortDir(): void
    {
        $this->resetPage('activityPage');
    }

    public function render()
    {
        return view('livewire.tickets.manager-dashboard', [
            'statusCounts' => $this->computeStatusCounts(),
            'categoryCounts' => $this->computeCategoryCounts(),
            'createdWeek' => $this->countCreatedSince(now()->startOfWeek()),
            'createdMonth' => $this->countCreatedSince(now()->startOfMonth()),
            'avgResolutionHrs' => $this->computeAvgResolutionHours(),
            'slaCompliance' => $this->computeSlaCompliance(),
            'breachedCount' => $this->countBreached(),
            'breachedTickets' => $this->fetchBreachedTickets(),
            'escalationQueue' => $this->fetchEscalationQueue(),
            'unassignedCount' => $this->countUnassigned(),
            'teamWorkload' => $this->fetchTeamWorkload(),
            'recentActivity' => $this->fetchRecentActivity(),
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

    private function computeCategoryCounts(): Collection
    {
        return DB::table('tickets')
            ->join('categories', 'tickets.category_id', '=', 'categories.id')
            ->whereNull('tickets.deleted_at')
            ->select('categories.name_en', 'categories.name_ar', DB::raw('COUNT(*) as total'))
            ->groupBy('categories.id', 'categories.name_en', 'categories.name_ar')
            ->orderByDesc('total')
            ->get();
    }

    private function countCreatedSince(Carbon $since): int
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

    private function fetchBreachedTickets(): LengthAwarePaginator
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
            ->paginate(config('ticketing.dashboard.per_page', 25), ['*'], 'breachedPage');
    }

    // ── Escalation queue ──────────────────────────────────────────────────────

    private function fetchEscalationQueue(): Collection
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

    private function fetchTeamWorkload(): LengthAwarePaginator
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
            ->paginate(config('ticketing.dashboard.per_page', 25), ['*'], 'workloadPage');
    }

    // ── Recent activity feed ──────────────────────────────────────────────────

    private function fetchRecentActivity(): LengthAwarePaginator
    {
        $col = in_array($this->sortBy, self::ALLOWED_SORTS, true) ? $this->sortBy : 'updated_at';
        $dir = in_array(strtolower($this->sortDir), ['asc', 'desc'], true) ? $this->sortDir : 'desc';

        $query = DB::table('tickets')
            ->leftJoin('users as requester', 'tickets.requester_id', '=', 'requester.id')
            ->leftJoin('users as tech', 'tickets.assigned_to', '=', 'tech.id')
            ->whereNull('tickets.deleted_at')
            ->select(
                'tickets.id',
                'tickets.display_number',
                'tickets.subject',
                'tickets.status',
                'tickets.priority',
                'tickets.updated_at',
                'tickets.created_at',
                'requester.full_name as requester_name',
                'tech.full_name as tech_name',
            );

        if ($col === 'priority') {
            $order = $dir === 'desc' ? 'ASC' : 'DESC';
            $query->orderByRaw("CASE tickets.priority
                WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3
            END {$order}");
        } else {
            $query->orderBy("tickets.{$col}", $dir);
        }

        return $query->paginate(config('ticketing.dashboard.per_page', 25), ['*'], 'activityPage');
    }
}
