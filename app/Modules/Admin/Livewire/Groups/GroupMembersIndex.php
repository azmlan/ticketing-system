<?php

namespace App\Modules\Admin\Livewire\Groups;

use App\Modules\Admin\Models\Group;
use App\Modules\Shared\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class GroupMembersIndex extends Component
{
    use AuthorizesRequests;

    public Group $group;

    public string $techSearch = '';

    public ?string $selectedManagerId = '';

    public function mount(Group $group): void
    {
        abort_unless(
            auth()->user()?->can('group.manage-members') || auth()->user()?->can('group.manage-manager'),
            403
        );
        $this->group = $group;
        $this->selectedManagerId = $group->manager_id ?? '';
    }

    public function addMember(string $userId): void
    {
        $this->authorize('group.manage-members');
        User::where('is_tech', true)->findOrFail($userId);
        $this->group->users()->syncWithoutDetaching([$userId]);
        $this->techSearch = '';
        session()->flash('success', __('admin.groups.member_added'));
    }

    public function removeMember(string $userId): void
    {
        $this->authorize('group.manage-members');
        $this->group->users()->detach($userId);

        if ($this->group->manager_id === $userId) {
            $this->group->update(['manager_id' => null]);
            $this->selectedManagerId = '';
        }

        session()->flash('success', __('admin.groups.member_removed'));
    }

    public function saveManager(): void
    {
        $this->authorize('group.manage-manager');

        $managerId = $this->selectedManagerId ?: null;

        if ($managerId) {
            User::where('is_tech', true)->findOrFail($managerId);
        }

        $this->group->update(['manager_id' => $managerId]);
        session()->flash('success', __('admin.groups.manager_updated'));
    }

    public function render()
    {
        $members = $this->group->users()->orderBy('full_name')->get();
        $memberIds = $members->pluck('id')->all();

        $availableTechs = User::where('is_tech', true)
            ->whereNotIn('id', $memberIds)
            ->when($this->techSearch, fn ($q) => $q->where(function ($q) {
                $q->where('full_name', 'like', '%'.$this->techSearch.'%')
                    ->orWhere('email', 'like', '%'.$this->techSearch.'%');
            }))
            ->orderBy('full_name')
            ->limit(20)
            ->get();

        return view('livewire.admin.groups.group-members-index', compact('members', 'availableTechs'));
    }
}
