<?php

namespace App\Modules\CSAT\Livewire;

use App\Modules\CSAT\Models\CsatRating;
use Livewire\Component;

class CsatRatingSection extends Component
{
    public string $ticketId;

    public ?CsatRating $csatRating = null;

    public int $rating = 0;

    public string $comment = '';

    public string $viewMode = 'none'; // none | pending_form | read_only

    public function mount(string $ticketId): void
    {
        $this->ticketId = $ticketId;
        $this->loadRating();
    }

    private function loadRating(): void
    {
        $this->csatRating = CsatRating::where('ticket_id', $this->ticketId)
            ->with(['tech'])
            ->first();

        if (! $this->csatRating) {
            $this->viewMode = 'none';

            return;
        }

        $user = auth()->user();
        $this->viewMode = $this->resolveViewMode($user);
    }

    private function resolveViewMode($user): string
    {
        if (! $user) {
            return 'none';
        }

        $rating = $this->csatRating;

        // IT Manager / super user — see all submitted ratings
        if ($user->is_super_user || $user->hasPermission('ticket.view-all')) {
            return $rating->status === 'submitted' ? 'read_only' : 'none';
        }

        // Requester — see own pending form or submitted read-only
        if ($user->id === $rating->requester_id) {
            if ($rating->status === 'pending') {
                return 'pending_form';
            }
            if ($rating->status === 'submitted') {
                return 'read_only';
            }

            return 'none';
        }

        // Assigned tech — see submitted rating only
        if ($user->is_tech && $user->id === $rating->tech_id) {
            return $rating->status === 'submitted' ? 'read_only' : 'none';
        }

        return 'none';
    }

    public function submit(): void
    {
        $this->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
        ], [
            'rating.required' => __('csat.validation.rating_required'),
            'rating.min' => __('csat.validation.rating_range'),
            'rating.max' => __('csat.validation.rating_range'),
        ]);

        $user = auth()->user();

        if (! $this->csatRating || $this->csatRating->status !== 'pending') {
            $this->addError('rating', __('csat.validation.already_rated'));

            return;
        }

        if ($this->csatRating->requester_id !== $user->id) {
            abort(403);
        }

        $this->csatRating->update([
            'rating' => $this->rating,
            'comment' => $this->comment ?: null,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->loadRating();
    }

    public function render()
    {
        return view('livewire.csat.rating-section');
    }
}
