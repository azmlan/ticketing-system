<?php

namespace App\Modules\Tickets\Livewire;

use App\Modules\Tickets\Models\Ticket;
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

        return view('livewire.tickets.ticket-list', compact('tickets'));
    }
}
