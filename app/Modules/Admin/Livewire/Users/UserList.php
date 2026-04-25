<?php

namespace App\Modules\Admin\Livewire\Users;

use App\Modules\Shared\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class UserList extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';
    public string $filterRole = '';   // '', 'employee', 'tech', 'it_manager'
    public string $filterStatus = ''; // '', 'active', 'inactive'

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->can('user.promote') || auth()->user()?->can('user.manage-permissions'),
            403,
        );
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterRole(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $users = User::withTrashed()
            ->with(['department'])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('full_name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            }))
            ->when($this->filterRole === 'employee', fn ($q) => $q->where('is_tech', false)->where('is_super_user', false))
            ->when($this->filterRole === 'tech', fn ($q) => $q->where('is_tech', true)->where('is_super_user', false))
            ->when($this->filterRole === 'it_manager', fn ($q) => $q->where('is_super_user', true))
            ->when($this->filterStatus === 'active', fn ($q) => $q->whereNull('deleted_at'))
            ->when($this->filterStatus === 'inactive', fn ($q) => $q->whereNotNull('deleted_at'))
            ->orderBy('full_name')
            ->paginate(20);

        return view('livewire.admin.users.user-list', compact('users'));
    }
}
