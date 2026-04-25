<?php

namespace App\Modules\Precedent\Livewire;

use App\Modules\Precedent\Models\Resolution;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Services\RichTextSanitizer;
use App\Modules\Tickets\Services\TicketStateMachine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ResolveModal extends Component
{
    public Ticket $ticket;

    public bool $open = false;

    /** 'write' | 'link' */
    public string $mode = 'write';

    public string $summary = '';

    public string $rootCause = '';

    public string $stepsTaken = '';

    public string $partsResources = '';

    public string $timeSpentMinutes = '';

    public string $resolutionType = '';

    public string $linkedResolutionId = '';

    public string $linkNotes = '';

    public string $searchQuery = '';

    #[Computed]
    public function searchResults()
    {
        if (mb_strlen(trim($this->searchQuery)) < 2) {
            return collect();
        }

        return Resolution::where('summary', 'like', '%' . $this->searchQuery . '%')
            ->where('ticket_id', '!=', $this->ticket->id)
            ->orderByDesc('usage_count')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function selectedResolution(): ?Resolution
    {
        if ($this->linkedResolutionId === '') {
            return null;
        }

        return Resolution::find($this->linkedResolutionId);
    }

    #[On('open-resolve-modal')]
    public function openModal(): void
    {
        $user = auth()->user();

        if (! $this->canResolve($user)) {
            abort(403);
        }

        $this->reset(['summary', 'rootCause', 'stepsTaken', 'partsResources', 'timeSpentMinutes',
            'resolutionType', 'mode', 'linkedResolutionId', 'linkNotes', 'searchQuery']);
        $this->resetValidation();
        $this->open = true;
    }

    public function cancel(): void
    {
        $this->open = false;
        $this->reset(['summary', 'rootCause', 'stepsTaken', 'partsResources', 'timeSpentMinutes',
            'resolutionType', 'mode', 'linkedResolutionId', 'linkNotes', 'searchQuery']);
        $this->resetValidation();
    }

    public function switchMode(string $mode): void
    {
        $this->mode              = $mode;
        $this->stepsTaken        = '';
        $this->linkedResolutionId = '';
        $this->linkNotes         = '';
        $this->searchQuery       = '';
        $this->resetValidation();
    }

    public function selectResolution(string $id): void
    {
        $this->linkedResolutionId = $id;
        $this->searchQuery        = '';
    }

    public function clearLinkedResolution(): void
    {
        $this->linkedResolutionId = '';
    }

    public function submit(): void
    {
        $user = auth()->user();

        if (! $this->canResolve($user)) {
            abort(403);
        }

        // XOR: cannot provide both paths simultaneously
        if ($this->stepsTaken !== '' && $this->linkedResolutionId !== '') {
            $this->addError('linkedResolutionId', __('validation.prohibited'));

            return;
        }

        $rules = [
            'summary'          => ['required', 'string', 'max:500'],
            'rootCause'        => ['nullable', 'string', 'max:500'],
            'resolutionType'   => ['required', Rule::in(['known_fix', 'workaround', 'escalated_externally', 'other'])],
            'partsResources'   => ['nullable', 'string'],
            'timeSpentMinutes' => ['nullable', 'integer', 'min:1', 'max:99999'],
            'linkNotes'        => ['nullable', 'string'],
        ];

        if ($this->mode === 'link') {
            $rules['linkedResolutionId'] = ['required', 'string', Rule::exists('resolutions', 'id')];
            $rules['stepsTaken']         = ['nullable'];
        } else {
            $rules['stepsTaken']         = ['required', 'string', 'min:1'];
            $rules['linkedResolutionId'] = ['nullable'];
        }

        $this->validate($rules);

        DB::transaction(function () use ($user) {
            app(TicketStateMachine::class)->transition($this->ticket, 'resolved', $user);

            if ($this->mode === 'link') {
                $linkedId = $this->linkedResolutionId;

                Resolution::create([
                    'ticket_id'            => $this->ticket->id,
                    'summary'              => $this->summary,
                    'root_cause'           => $this->rootCause ?: null,
                    'resolution_type'      => $this->resolutionType,
                    'parts_resources'      => $this->partsResources ?: null,
                    'time_spent_minutes'   => $this->timeSpentMinutes !== '' ? (int) $this->timeSpentMinutes : null,
                    'linked_resolution_id' => $linkedId,
                    'link_notes'           => $this->linkNotes ?: null,
                    'created_by'           => $user->id,
                ]);

                DB::table('resolutions')->where('id', $linkedId)->increment('usage_count');
            } else {
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
            }
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
