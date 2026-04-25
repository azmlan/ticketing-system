<?php

namespace App\Modules\Admin\Livewire\Users;

use App\Modules\Admin\Events\UserPromotedToTech;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\TechProfile;
use App\Modules\Shared\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class UserDetail extends Component
{
    use AuthorizesRequests;

    public User $targetUser;

    /** @var array<string> Permission IDs currently checked for the viewed user */
    public array $selectedPermissions = [];

    public bool $showPromoteConfirm = false;

    public function mount(User $user): void
    {
        abort_unless(
            auth()->user()?->can('user.promote') || auth()->user()?->can('user.manage-permissions'),
            403,
        );

        $this->targetUser = $user;
        $this->selectedPermissions = $user->permissions()->pluck('permissions.id')->toArray();
    }

    public function confirmPromote(): void
    {
        $this->authorize('user.promote');
        $this->showPromoteConfirm = true;
    }

    public function cancelPromote(): void
    {
        $this->showPromoteConfirm = false;
    }

    public function promote(): void
    {
        $this->authorize('user.promote');

        if ($this->targetUser->is_tech) {
            session()->flash('error', __('admin.users.already_tech'));
            $this->showPromoteConfirm = false;
            return;
        }

        DB::transaction(function () {
            $this->targetUser->update(['is_tech' => true]);

            if (! $this->targetUser->techProfile()->exists()) {
                TechProfile::create([
                    'user_id'     => $this->targetUser->id,
                    'promoted_by' => auth()->id(),
                    'promoted_at' => now(),
                ]);
            }
        });

        UserPromotedToTech::dispatch($this->targetUser->fresh(), auth()->user());

        $this->targetUser->refresh();
        $this->showPromoteConfirm = false;
        session()->flash('success', __('admin.users.promoted'));
    }

    public function savePermissions(): void
    {
        $this->authorize('user.manage-permissions');

        if ($this->targetUser->is_super_user) {
            session()->flash('error', __('admin.users.permissions_blocked'));
            return;
        }

        $validIds = Permission::pluck('id')->toArray();
        $selected = array_values(array_intersect($this->selectedPermissions, $validIds));

        $syncData = [];
        foreach ($selected as $permId) {
            $syncData[$permId] = [
                'granted_by' => auth()->id(),
                'granted_at' => now(),
            ];
        }

        $this->targetUser->permissions()->sync($syncData);
        session()->flash('success', __('admin.users.permissions_saved'));
    }

    public function render()
    {
        $permissions = Permission::orderBy('group_key')->orderBy('key')->get()->groupBy('group_key');

        return view('livewire.admin.users.user-detail', [
            'targetUser'  => $this->targetUser->load(['department', 'location', 'techProfile']),
            'permissions' => $permissions,
            'canPromote'  => auth()->user()?->can('user.promote'),
            'canManagePerms' => auth()->user()?->can('user.manage-permissions'),
        ]);
    }
}
