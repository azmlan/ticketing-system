<?php

namespace App\Modules\Precedent\Livewire;

use App\Modules\Precedent\Models\Resolution;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Models\Ticket;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AutoSuggestPanel extends Component
{
    public Ticket $ticket;

    public bool $collapsed = true;

    public function toggle(): void
    {
        $this->collapsed = ! $this->collapsed;
    }

    #[Computed]
    public function suggestions()
    {
        return Resolution::whereHas('ticket', function ($q) {
            $q->where('status', TicketStatus::Resolved->value)
              ->where('category_id', $this->ticket->category_id);

            if ($this->ticket->subcategory_id !== null) {
                $q->where('subcategory_id', $this->ticket->subcategory_id);
            } else {
                $q->whereNull('subcategory_id');
            }
        })
        ->where('ticket_id', '!=', $this->ticket->id)
        ->with(['ticket.customFieldValues.field'])
        ->orderByDesc('usage_count')
        ->orderByDesc('created_at')
        ->get();
    }

    public function render()
    {
        return view('livewire.precedent.auto-suggest-panel');
    }
}
