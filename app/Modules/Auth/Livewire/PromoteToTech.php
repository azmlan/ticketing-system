<?php

namespace App\Modules\Auth\Livewire;

use App\Modules\Shared\Models\TechProfile;
use App\Modules\Shared\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PromoteToTech extends Component
{
    public string $user_id  = '';
    public bool   $promoted = false;

    public function mount(): void
    {
        if (! Auth::user()->can('user.promote')) {
            abort(403);
        }
    }

    public function promote(): void
    {
        if (! Auth::user()->can('user.promote')) {
            abort(403);
        }

        $this->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $target = User::findOrFail($this->user_id);

        if ($target->is_tech) {
            $this->addError('user_id', __('promote.already_tech'));
            return;
        }

        DB::transaction(function () use ($target) {
            $target->update(['is_tech' => true]);

            TechProfile::create([
                'user_id'     => $target->id,
                'promoted_by' => Auth::id(),
                'promoted_at' => now(),
            ]);
        });

        $this->promoted = true;
        $this->user_id  = '';
    }

    public function render()
    {
        $candidates = User::where('is_tech', false)
            ->whereNull('deleted_at')
            ->orderBy('full_name')
            ->get();

        return view('livewire.auth.promote-to-tech', compact('candidates'));
    }
}
