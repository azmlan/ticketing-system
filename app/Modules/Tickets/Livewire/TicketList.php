<?php

namespace App\Modules\Tickets\Livewire;

use App\Modules\Tickets\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class TicketList extends Component
{
    use WithPagination;

    public function render()
    {
        $tickets = Ticket::with(['category', 'assignedTo'])
            ->orderByDesc('created_at')
            ->paginate(15);

        $slaMap = DB::table('ticket_sla')
            ->whereIn('ticket_id', $tickets->pluck('id')->all())
            ->get()
            ->keyBy('ticket_id');

        $compliance = $this->computeCompliance();

        return view('livewire.tickets.ticket-list', compact('tickets', 'slaMap', 'compliance'));
    }

    private function computeCompliance(): array
    {
        $rows = DB::table('ticket_sla')->select('resolution_status')->get();

        $total = $rows->count();
        if ($total === 0) {
            return ['total' => 0, 'breached' => 0, 'percent' => null];
        }

        $breached = $rows->where('resolution_status', 'breached')->count();
        $percent  = (int) round((($total - $breached) / $total) * 100);

        return ['total' => $total, 'breached' => $breached, 'percent' => $percent];
    }
}
