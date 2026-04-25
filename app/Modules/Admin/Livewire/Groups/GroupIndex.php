<?php

namespace App\Modules\Admin\Livewire\Groups;

use App\Modules\Admin\Models\Group;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class GroupIndex extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public ?string $showFormFor = null; // 'create' or group ULID for edit

    public string $formNameAr = '';

    public string $formNameEn = '';

    public bool $formIsActive = true;

    protected function rules(): array
    {
        return [
            'formNameAr' => ['required', 'string', 'max:255'],
            'formNameEn' => ['required', 'string', 'max:255'],
            'formIsActive' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'formNameAr.required' => __('validation.required', ['attribute' => __('admin.groups.name_ar')]),
            'formNameEn.required' => __('validation.required', ['attribute' => __('admin.groups.name_en')]),
        ];
    }

    public function mount(): void
    {
        $this->authorize('group.manage');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorize('group.manage');
        $this->resetForm();
        $this->showFormFor = 'create';
    }

    public function openEdit(string $id): void
    {
        $this->authorize('group.manage');
        $group = Group::findOrFail($id);
        $this->formNameAr = $group->name_ar;
        $this->formNameEn = $group->name_en;
        $this->formIsActive = $group->is_active;
        $this->showFormFor = $id;
    }

    public function save(): void
    {
        $this->authorize('group.manage');
        $this->validate();

        if ($this->showFormFor === 'create') {
            Group::create([
                'name_ar' => $this->formNameAr,
                'name_en' => $this->formNameEn,
                'is_active' => $this->formIsActive,
            ]);
            session()->flash('success', __('admin.groups.created'));
        } else {
            $group = Group::findOrFail($this->showFormFor);
            $group->update([
                'name_ar' => $this->formNameAr,
                'name_en' => $this->formNameEn,
                'is_active' => $this->formIsActive,
            ]);
            session()->flash('success', __('admin.groups.updated'));
        }

        $this->resetForm();
    }

    public function toggleActive(string $id): void
    {
        $this->authorize('group.manage');
        $group = Group::findOrFail($id);
        $group->update(['is_active' => ! $group->is_active]);

        $msg = $group->is_active
            ? __('admin.groups.activated')
            : __('admin.groups.deactivated');
        session()->flash('success', $msg);
    }

    public function delete(string $id): void
    {
        $this->authorize('group.manage');
        Group::findOrFail($id)->delete();
        session()->flash('success', __('admin.groups.deleted'));
        $this->resetPage();
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->showFormFor = null;
        $this->formNameAr = '';
        $this->formNameEn = '';
        $this->formIsActive = true;
        $this->resetValidation();
    }

    public function render()
    {
        $groups = Group::withTrashed()
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name_ar', 'like', '%'.$this->search.'%')
                    ->orWhere('name_en', 'like', '%'.$this->search.'%');
            }))
            ->withCount('users')
            ->with('manager')
            ->orderBy('name_en')
            ->paginate(15);

        return view('livewire.admin.groups.group-index', compact('groups'));
    }
}
