<?php

namespace App\Modules\Admin\Livewire\Categories;

use App\Modules\Admin\Models\Category;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class CategoryIndex extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';
    public ?string $showFormFor = null; // 'create' or category ULID for edit
    public string $formNameAr = '';
    public string $formNameEn = '';
    public string $formGroupId = '';
    public bool $formIsActive = true;

    protected function rules(): array
    {
        return [
            'formNameAr'  => ['required', 'string', 'max:255'],
            'formNameEn'  => ['required', 'string', 'max:255'],
            'formGroupId' => ['required', 'exists:groups,id'],
            'formIsActive' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'formNameAr.required'  => __('validation.required', ['attribute' => __('admin.categories.name_ar')]),
            'formNameEn.required'  => __('validation.required', ['attribute' => __('admin.categories.name_en')]),
            'formGroupId.required' => __('validation.required', ['attribute' => __('admin.categories.group')]),
            'formGroupId.exists'   => __('validation.exists', ['attribute' => __('admin.categories.group')]),
        ];
    }

    public function mount(): void
    {
        $this->authorize('category.manage');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorize('category.manage');
        $this->resetForm();
        $this->showFormFor = 'create';
    }

    public function openEdit(string $id): void
    {
        $this->authorize('category.manage');
        $category = Category::findOrFail($id);
        $this->formNameAr  = $category->name_ar;
        $this->formNameEn  = $category->name_en;
        $this->formGroupId = $category->group_id;
        $this->formIsActive = $category->is_active;
        $this->showFormFor = $id;
    }

    public function save(): void
    {
        $this->authorize('category.manage');
        $this->validate();

        if ($this->showFormFor === 'create') {
            Category::create([
                'name_ar'   => $this->formNameAr,
                'name_en'   => $this->formNameEn,
                'group_id'  => $this->formGroupId,
                'is_active' => $this->formIsActive,
                'version'   => 1,
            ]);
            session()->flash('success', __('admin.categories.created'));
        } else {
            $category = Category::findOrFail($this->showFormFor);
            $category->update([
                'name_ar'   => $this->formNameAr,
                'name_en'   => $this->formNameEn,
                'group_id'  => $this->formGroupId,
                'is_active' => $this->formIsActive,
                'version'   => $category->version + 1,
            ]);
            session()->flash('success', __('admin.categories.updated'));
        }

        $this->resetForm();
    }

    public function toggleActive(string $id): void
    {
        $this->authorize('category.manage');
        $category = Category::findOrFail($id);
        $category->update(['is_active' => ! $category->is_active]);

        $msg = $category->is_active
            ? __('admin.categories.activated')
            : __('admin.categories.deactivated');
        session()->flash('success', $msg);
    }

    public function delete(string $id): void
    {
        $this->authorize('category.manage');
        Category::findOrFail($id)->delete();
        session()->flash('success', __('admin.categories.deleted'));
        $this->resetPage();
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->showFormFor = null;
        $this->formNameAr  = '';
        $this->formNameEn  = '';
        $this->formGroupId = '';
        $this->formIsActive = true;
        $this->resetValidation();
    }

    public function render()
    {
        $categories = Category::withTrashed()
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name_ar', 'like', '%' . $this->search . '%')
                  ->orWhere('name_en', 'like', '%' . $this->search . '%');
            }))
            ->with('group')
            ->orderBy('name_en')
            ->paginate(15);

        $groups = \App\Modules\Admin\Models\Group::active()->orderBy('name_en')->get();

        return view('livewire.admin.categories.category-index', compact('categories', 'groups'));
    }
}
