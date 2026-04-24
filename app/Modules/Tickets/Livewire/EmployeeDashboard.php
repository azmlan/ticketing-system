<?php

namespace App\Modules\Tickets\Livewire;

use App\Modules\Tickets\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class EmployeeDashboard extends Component
{
    public string $search = '';
    public string $statusFilter = '';

    // Maps UI tab labels to actual ticket status values
    private const OPEN_STATUSES = [
        'awaiting_assignment',
        'in_progress',
        'on_hold',
        'awaiting_approval',
        'action_required',
        'awaiting_final_approval',
    ];

    public function updatedSearch(): void
    {
        // keep reactive — no extra logic needed
    }

    public function updatedStatusFilter(): void
    {
        // keep reactive — no extra logic needed
    }

    public function render()
    {
        $tickets = $this->buildTicketQuery()->with(['category'])->orderByDesc('created_at')->get();

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
            $query->where('subject', 'LIKE', '%' . $this->search . '%');
        }

        return $query;
    }

    private function computeCounts(): array
    {
        return [
            'open'      => Ticket::query()->whereIn('status', self::OPEN_STATUSES)->count(),
            'resolved'  => Ticket::query()->where('status', 'resolved')->count(),
            'closed'    => Ticket::query()->where('status', 'closed')->count(),
            'cancelled' => Ticket::query()->where('status', 'cancelled')->count(),
        ];
    }
}
