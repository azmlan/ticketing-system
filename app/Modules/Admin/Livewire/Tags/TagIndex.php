<?php

namespace App\Modules\Admin\Livewire\Tags;

use App\Modules\Admin\Models\Tag;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class TagIndex extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public ?string $showFormFor = null; // 'create' or tag ULID for edit

    public string $formNameAr = '';

    public string $formNameEn = '';

    public string $formColor = '#3B82F6';

    public bool $formIsActive = true;

    protected function rules(): array
    {
        return [
            'formNameAr'  => ['required', 'string', 'max:100'],
            'formNameEn'  => ['required', 'string', 'max:100'],
            'formColor'   => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'formIsActive' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'formNameAr.required' => __('validation.required', ['attribute' => __('admin.tags.name_ar')]),
            'formNameEn.required' => __('validation.required', ['attribute' => __('admin.tags.name_en')]),
            'formColor.required'  => __('validation.required', ['attribute' => __('admin.tags.color')]),
            'formColor.regex'     => __('admin.tags.invalid_color'),
        ];
    }

    public function mount(): void
    {
        $this->authorize('system.manage-tags');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorize('system.manage-tags');
        $this->resetForm();
        $this->showFormFor = 'create';
    }

    public function openEdit(string $id): void
    {
        $this->authorize('system.manage-tags');
        $tag = Tag::findOrFail($id);
        $this->formNameAr  = $tag->name_ar;
        $this->formNameEn  = $tag->name_en;
        $this->formColor   = $tag->color ?? '#3B82F6';
        $this->formIsActive = $tag->is_active;
        $this->showFormFor = $id;
    }

    public function save(): void
    {
        $this->authorize('system.manage-tags');
        $this->validate();

        if ($this->showFormFor === 'create') {
            Tag::create([
                'name_ar'   => $this->formNameAr,
                'name_en'   => $this->formNameEn,
                'color'     => $this->formColor,
                'is_active' => $this->formIsActive,
            ]);
            session()->flash('success', __('admin.tags.created'));
        } else {
            $tag = Tag::findOrFail($this->showFormFor);
            $tag->update([
                'name_ar'   => $this->formNameAr,
                'name_en'   => $this->formNameEn,
                'color'     => $this->formColor,
                'is_active' => $this->formIsActive,
            ]);
            session()->flash('success', __('admin.tags.updated'));
        }

        $this->resetForm();
    }

    public function toggleActive(string $id): void
    {
        $this->authorize('system.manage-tags');
        $tag = Tag::findOrFail($id);
        $tag->update(['is_active' => ! $tag->is_active]);

        $msg = $tag->is_active
            ? __('admin.tags.activated')
            : __('admin.tags.deactivated');
        session()->flash('success', $msg);
    }

    public function delete(string $id): void
    {
        $this->authorize('system.manage-tags');
        Tag::findOrFail($id)->delete();
        session()->flash('success', __('admin.tags.deleted'));
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
        $this->formColor   = '#3B82F6';
        $this->formIsActive = true;
        $this->resetValidation();
    }

    public function render()
    {
        $tags = Tag::withTrashed()
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name_ar', 'like', '%'.$this->search.'%')
                    ->orWhere('name_en', 'like', '%'.$this->search.'%');
            }))
            ->orderBy('name_en')
            ->paginate(15);

        return view('livewire.admin.tags.tag-index', compact('tags'));
    }
}
