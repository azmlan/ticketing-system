<?php

namespace App\Modules\Tickets\Livewire;

use App\Modules\Tickets\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class EmployeeDashboard extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $sortBy = 'created_at';

    public string $sortDir = 'desc';

    private const OPEN_STATUSES = [
        'awaiting_assignment',
        'in_progress',
        'on_hold',
        'awaiting_approval',
        'action_required',
        'awaiting_final_approval',
    ];

    private const ALLOWED_SORTS = ['created_at', 'priority', 'updated_at'];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSortBy(): void
    {
        $this->resetPage();
    }

    public function updatedSortDir(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $tickets = $this->buildTicketQuery()
            ->with(['category'])
            ->paginate(config('ticketing.dashboard.per_page', 25));

        $slaMap = DB::table('ticket_sla')
            ->whereIn('ticket_id', $tickets->pluck('id')->all())
            ->get()
            ->keyBy('ticket_id');

        $counts = $this->computeCounts();

        return view('livewire.tickets.employee-dashboard', compact('tickets', 'slaMap', 'counts'));
    }

    private function buildTicketQuery()
    {
        $query = Ticket::query();

        if ($this->statusFilter === 'open') {
            $query->whereIn('status', self::OPEN_STATUSES);
        } elseif ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        // Subject-only search — LIKE is not FULLTEXT; CLAUDE.md invariant only forbids raw FULLTEXT
        if ($this->search !== '') {
            $query->where('subject', 'LIKE', '%'.$this->search.'%');
        }

        $this->applySort($query);

        return $query;
    }

    private function applySort($query): void
    {
        $col = in_array($this->sortBy, self::ALLOWED_SORTS, true) ? $this->sortBy : 'created_at';
        $dir = in_array(strtolower($this->sortDir), ['asc', 'desc'], true) ? $this->sortDir : 'desc';

        if ($col === 'priority') {
            $order = $dir === 'desc' ? 'ASC' : 'DESC';
            $query->orderByRaw("CASE priority
                WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3
            END {$order}");
        } else {
            $query->orderBy($col, $dir);
        }
    }

    private function computeCounts(): array
    {
        return [
            'open' => Ticket::query()->whereIn('status', self::OPEN_STATUSES)->count(),
            'resolved' => Ticket::query()->where('status', 'resolved')->count(),
            'closed' => Ticket::query()->where('status', 'closed')->count(),
            'cancelled' => Ticket::query()->where('status', 'cancelled')->count(),
        ];
    }
}
