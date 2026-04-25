<?php

namespace App\Modules\CSAT\Livewire;

use App\Modules\CSAT\Models\CsatRating;
use Livewire\Component;

class CsatPromptModal extends Component
{
    public ?CsatRating $csatRating = null;

    public int $rating = 0;

    public string $comment = '';

    public bool $visible = false;

    public function mount(): void
    {
        $user = auth()->user();

        if (! $user || $user->is_tech || $user->is_super_user) {
            return;
        }

        // Dismissed this session — don't show again until next login
        if (session()->has('csat_modal_dismissed')) {
            return;
        }

        $this->csatRating = CsatRating::pending()
            ->where('requester_id', $user->id)
            ->where('expires_at', '>', now())
            ->oldest('expires_at')
            ->with(['ticket', 'tech'])
            ->first();

        $this->visible = $this->csatRating !== null;
    }

    public function dismiss(): void
    {
        if (! $this->csatRating) {
            return;
        }

        $this->csatRating->increment('dismissed_count');
        session()->put('csat_modal_dismissed', true);
        $this->visible = false;
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

        if (! $this->csatRating || $this->csatRating->status !== 'pending') {
            $this->addError('rating', __('csat.validation.already_rated'));

            return;
        }

        $this->csatRating->update([
            'rating' => $this->rating,
            'comment' => $this->comment ?: null,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        session()->put('csat_modal_dismissed', true);
        $this->visible = false;
    }

    public function render()
    {
        return view('livewire.csat.prompt-modal');
    }
}
