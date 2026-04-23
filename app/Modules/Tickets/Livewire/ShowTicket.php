<?php

namespace App\Modules\Tickets\Livewire;

use App\Modules\Tickets\Models\Ticket;
use Livewire\Attributes\Layout;
use Livewire\Component;

// Stub — full implementation in Task 2.7
#[Layout('layouts.app')]
class ShowTicket extends Component
{
    public Ticket $ticket;

    public function render()
    {
        return view('livewire.tickets.show-ticket');
    }
}
