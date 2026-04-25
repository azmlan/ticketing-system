<?php

namespace App\Modules\Admin\Livewire\Categories;

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Subcategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class SubcategoryIndex extends Component
{
    use AuthorizesRequests, WithPagination;

    public Category $category;
    public string $search = '';
    public ?string $showFormFor = null;
    public string $formNameAr = '';
    public string $formNameEn = '';
    public bool $formIsRequired = false;
    public bool $formIsActive = true;

    protected function rules(): array
    {
        return [
            'formNameAr'    => ['required', 'string', 'max:255'],
            'formNameEn'    => ['required', 'string', 'max:255'],
            'formIsRequired' => ['boolean'],
            'formIsActive'  => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'formNameAr.required' => __('validation.required', ['attribute' => __('admin.subcategories.name_ar')]),
            'formNameEn.required' => __('validation.required', ['attribute' => __('admin.subcategories.name_en')]),
        ];
    }

    public function mount(Category $category): void
    {
        $this->authorize('category.manage');
        $this->category = $category;
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
        $sub = Subcategory::findOrFail($id);
        $this->formNameAr    = $sub->name_ar;
        $this->formNameEn    = $sub->name_en;
        $this->formIsRequired = $sub->is_required;
        $this->formIsActive  = $sub->is_active;
        $this->showFormFor   = $id;
    }

    public function save(): void
    {
        $this->authorize('category.manage');
        $this->validate();

        if ($this->showFormFor === 'create') {
            $this->category->subcategories()->create([
                'name_ar'     => $this->formNameAr,
                'name_en'     => $this->formNameEn,
                'is_required' => $this->formIsRequired,
                'is_active'   => $this->formIsActive,
                'version'     => 1,
            ]);
            session()->flash('success', __('admin.subcategories.created'));
        } else {
            $sub = Subcategory::findOrFail($this->showFormFor);
            $sub->update([
                'name_ar'     => $this->formNameAr,
                'name_en'     => $this->formNameEn,
                'is_required' => $this->formIsRequired,
                'is_active'   => $this->formIsActive,
                'version'     => $sub->version + 1,
            ]);
            session()->flash('success', __('admin.subcategories.updated'));
        }

        $this->resetForm();
    }

    public function toggleActive(string $id): void
    {
        $this->authorize('category.manage');
        $sub = Subcategory::findOrFail($id);
        $sub->update(['is_active' => ! $sub->is_active]);

        $msg = $sub->is_active
            ? __('admin.subcategories.activated')
            : __('admin.subcategories.deactivated');
        session()->flash('success', $msg);
    }

    public function delete(string $id): void
    {
        $this->authorize('category.manage');
        Subcategory::findOrFail($id)->delete();
        session()->flash('success', __('admin.subcategories.deleted'));
        $this->resetPage();
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->showFormFor   = null;
        $this->formNameAr    = '';
        $this->formNameEn    = '';
        $this->formIsRequired = false;
        $this->formIsActive  = true;
        $this->resetValidation();
    }

    public function render()
    {
        $subcategories = Subcategory::withTrashed()
            ->where('category_id', $this->category->id)
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name_ar', 'like', '%' . $this->search . '%')
                  ->orWhere('name_en', 'like', '%' . $this->search . '%');
            }))
            ->orderBy('name_en')
            ->paginate(15);

        return view('livewire.admin.categories.subcategory-index', compact('subcategories'));
    }
}
