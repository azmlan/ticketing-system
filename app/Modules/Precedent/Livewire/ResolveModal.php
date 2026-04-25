<?php

namespace App\Modules\Precedent\Livewire;

use App\Modules\Precedent\Models\Resolution;
use App\Modules\Tickets\Exceptions\InvalidTicketTransitionException;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Services\RichTextSanitizer;
use App\Modules\Tickets\Services\TicketStateMachine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class ResolveModal extends Component
{
    public Ticket $ticket;

    public bool $open = false;

    public string $summary = '';

    public string $rootCause = '';

    public string $stepsTaken = '';

    public string $partsResources = '';

    public string $timeSpentMinutes = '';

    public string $resolutionType = '';

    #[On('open-resolve-modal')]
    public function openModal(): void
    {
        $user = auth()->user();

        if (! $this->canResolve($user)) {
            abort(403);
        }

        $this->reset(['summary', 'rootCause', 'stepsTaken', 'partsResources', 'timeSpentMinutes', 'resolutionType']);
        $this->resetValidation();
        $this->open = true;
    }

    public function cancel(): void
    {
        $this->open = false;
        $this->reset(['summary', 'rootCause', 'stepsTaken', 'partsResources', 'timeSpentMinutes', 'resolutionType']);
        $this->resetValidation();
    }

    public function submit(): void
    {
        $user = auth()->user();

        if (! $this->canResolve($user)) {
            abort(403);
        }

        $this->validate([
            'summary'          => ['required', 'string', 'max:500'],
            'rootCause'        => ['nullable', 'string', 'max:500'],
            'stepsTaken'       => ['required', 'string', 'min:1'],
            'partsResources'   => ['nullable', 'string'],
            'timeSpentMinutes' => ['nullable', 'integer', 'min:1', 'max:99999'],
            'resolutionType'   => ['required', Rule::in(['known_fix', 'workaround', 'escalated_externally', 'other'])],
        ]);

        DB::transaction(function () use ($user) {
            app(TicketStateMachine::class)->transition($this->ticket, 'resolved', $user);

            Resolution::create([
                'ticket_id'          => $this->ticket->id,
                'summary'            => $this->summary,
                'root_cause'         => $this->rootCause ?: null,
                'steps_taken'        => app(RichTextSanitizer::class)->sanitize($this->stepsTaken),
                'parts_resources'    => $this->partsResources ?: null,
                'time_spent_minutes' => $this->timeSpentMinutes !== '' ? (int) $this->timeSpentMinutes : null,
                'resolution_type'    => $this->resolutionType,
                'created_by'         => $user->id,
            ]);
        });

        $this->open = false;
        $this->ticket->refresh();
        $this->dispatch('ticket-resolved');
    }

    public function render()
    {
        return view('livewire.precedent.resolve-modal');
    }

    private function canResolve(?object $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->ticket->assigned_to === $user->id
            || $user->is_super_user
            || $user->hasPermission('ticket.resolve');
    }
}
