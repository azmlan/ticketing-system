<?php

namespace App\Modules\Admin\Livewire\Departments;

use App\Modules\Shared\Models\Department;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class DepartmentIndex extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public ?string $showFormFor = null; // 'create' or ULID for edit

    public string $formNameAr = '';

    public string $formNameEn = '';

    public int $formSortOrder = 0;

    public bool $formIsActive = true;

    protected function rules(): array
    {
        return [
            'formNameAr'    => ['required', 'string', 'max:255'],
            'formNameEn'    => ['required', 'string', 'max:255'],
            'formSortOrder' => ['required', 'integer', 'min:0'],
            'formIsActive'  => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'formNameAr.required' => __('validation.required', ['attribute' => __('admin.departments.name_ar')]),
            'formNameEn.required' => __('validation.required', ['attribute' => __('admin.departments.name_en')]),
        ];
    }

    public function mount(): void
    {
        $this->authorize('system.manage-departments');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorize('system.manage-departments');
        $this->resetForm();
        $this->showFormFor = 'create';
    }

    public function openEdit(string $id): void
    {
        $this->authorize('system.manage-departments');
        $dept = Department::withTrashed()->findOrFail($id);
        $this->formNameAr    = $dept->name_ar;
        $this->formNameEn    = $dept->name_en;
        $this->formSortOrder = $dept->sort_order;
        $this->formIsActive  = $dept->is_active;
        $this->showFormFor   = $id;
    }

    public function save(): void
    {
        $this->authorize('system.manage-departments');
        $this->validate();

        if ($this->showFormFor === 'create') {
            Department::create([
                'name_ar'    => $this->formNameAr,
                'name_en'    => $this->formNameEn,
                'sort_order' => $this->formSortOrder,
                'is_active'  => $this->formIsActive,
            ]);
            session()->flash('success', __('admin.departments.created'));
        } else {
            $dept = Department::withTrashed()->findOrFail($this->showFormFor);
            $dept->update([
                'name_ar'    => $this->formNameAr,
                'name_en'    => $this->formNameEn,
                'sort_order' => $this->formSortOrder,
                'is_active'  => $this->formIsActive,
            ]);
            session()->flash('success', __('admin.departments.updated'));
        }

        $this->resetForm();
    }

    public function toggleActive(string $id): void
    {
        $this->authorize('system.manage-departments');
        $dept = Department::findOrFail($id);
        $dept->update(['is_active' => ! $dept->is_active]);

        $msg = $dept->is_active
            ? __('admin.departments.activated')
            : __('admin.departments.deactivated');
        session()->flash('success', $msg);
    }

    public function delete(string $id): void
    {
        $this->authorize('system.manage-departments');
        Department::findOrFail($id)->delete();
        session()->flash('success', __('admin.departments.deleted'));
        $this->resetPage();
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->showFormFor  = null;
        $this->formNameAr   = '';
        $this->formNameEn   = '';
        $this->formSortOrder = 0;
        $this->formIsActive = true;
        $this->resetValidation();
    }

    public function render()
    {
        $departments = Department::withTrashed()
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name_ar', 'like', '%'.$this->search.'%')
                    ->orWhere('name_en', 'like', '%'.$this->search.'%');
            }))
            ->orderBy('sort_order')
            ->orderBy('name_en')
            ->paginate(15);

        return view('livewire.admin.departments.department-index', compact('departments'));
    }
}
