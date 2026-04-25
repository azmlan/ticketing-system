<?php

namespace App\Modules\Admin\Livewire\Locations;

use App\Modules\Shared\Models\Location;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class LocationIndex extends Component
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
            'formNameAr.required' => __('validation.required', ['attribute' => __('admin.locations.name_ar')]),
            'formNameEn.required' => __('validation.required', ['attribute' => __('admin.locations.name_en')]),
        ];
    }

    public function mount(): void
    {
        $this->authorize('system.manage-locations');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorize('system.manage-locations');
        $this->resetForm();
        $this->showFormFor = 'create';
    }

    public function openEdit(string $id): void
    {
        $this->authorize('system.manage-locations');
        $loc = Location::withTrashed()->findOrFail($id);
        $this->formNameAr    = $loc->name_ar;
        $this->formNameEn    = $loc->name_en;
        $this->formSortOrder = $loc->sort_order;
        $this->formIsActive  = $loc->is_active;
        $this->showFormFor   = $id;
    }

    public function save(): void
    {
        $this->authorize('system.manage-locations');
        $this->validate();

        if ($this->showFormFor === 'create') {
            Location::create([
                'name_ar'    => $this->formNameAr,
                'name_en'    => $this->formNameEn,
                'sort_order' => $this->formSortOrder,
                'is_active'  => $this->formIsActive,
            ]);
            session()->flash('success', __('admin.locations.created'));
        } else {
            $loc = Location::withTrashed()->findOrFail($this->showFormFor);
            $loc->update([
                'name_ar'    => $this->formNameAr,
                'name_en'    => $this->formNameEn,
                'sort_order' => $this->formSortOrder,
                'is_active'  => $this->formIsActive,
            ]);
            session()->flash('success', __('admin.locations.updated'));
        }

        $this->resetForm();
    }

    public function toggleActive(string $id): void
    {
        $this->authorize('system.manage-locations');
        $loc = Location::findOrFail($id);
        $loc->update(['is_active' => ! $loc->is_active]);

        $msg = $loc->is_active
            ? __('admin.locations.activated')
            : __('admin.locations.deactivated');
        session()->flash('success', $msg);
    }

    public function delete(string $id): void
    {
        $this->authorize('system.manage-locations');
        Location::findOrFail($id)->delete();
        session()->flash('success', __('admin.locations.deleted'));
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
        $locations = Location::withTrashed()
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name_ar', 'like', '%'.$this->search.'%')
                    ->orWhere('name_en', 'like', '%'.$this->search.'%');
            }))
            ->orderBy('sort_order')
            ->orderBy('name_en')
            ->paginate(15);

        return view('livewire.admin.locations.location-index', compact('locations'));
    }
}
