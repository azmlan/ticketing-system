<?php

namespace App\Modules\Admin\Livewire\CustomFields;

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\CustomField;
use App\Modules\Admin\Models\CustomFieldOption;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class CustomFieldIndex extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search        = '';
    public ?string $showFormFor  = null; // 'create' | field ULID (edit)
    public ?string $manageOptionsFor = null; // field ULID whose options panel is open

    // ── Field form ────────────────────────────────────────────────────────────
    public string $formNameAr        = '';
    public string $formNameEn        = '';
    public string $formFieldType     = 'text';
    public string $formScopeType     = 'global';
    public string $formScopeCategoryId = '';
    public bool   $formIsRequired    = false;
    public bool   $formIsActive      = true;

    // ── Option form ───────────────────────────────────────────────────────────
    public ?string $editOptionId     = null;
    public string  $optionValueAr    = '';
    public string  $optionValueEn    = '';

    protected function fieldRules(): array
    {
        return [
            'formNameAr'           => ['required', 'string', 'max:255'],
            'formNameEn'           => ['required', 'string', 'max:255'],
            'formFieldType'        => ['required', 'in:text,number,dropdown,multi_select,date,checkbox'],
            'formScopeType'        => ['required', 'in:global,category'],
            'formScopeCategoryId'  => ['nullable', 'exists:categories,id'],
            'formIsRequired'       => ['boolean'],
            'formIsActive'         => ['boolean'],
        ];
    }

    protected function optionRules(): array
    {
        return [
            'optionValueAr' => ['required', 'string', 'max:255'],
            'optionValueEn' => ['required', 'string', 'max:255'],
        ];
    }

    protected function messages(): array
    {
        return [
            'formNameAr.required'          => __('validation.required', ['attribute' => __('admin.custom_fields.name_ar')]),
            'formNameEn.required'          => __('validation.required', ['attribute' => __('admin.custom_fields.name_en')]),
            'formFieldType.in'             => __('admin.custom_fields.invalid_field_type'),
            'formScopeType.in'             => __('admin.custom_fields.invalid_scope_type'),
            'formScopeCategoryId.exists'   => __('validation.exists', ['attribute' => __('admin.custom_fields.scope_category')]),
            'optionValueAr.required'       => __('validation.required', ['attribute' => __('admin.custom_fields.option_value_ar')]),
            'optionValueEn.required'       => __('validation.required', ['attribute' => __('admin.custom_fields.option_value_en')]),
        ];
    }

    public function mount(): void
    {
        $this->authorize('system.manage-custom-fields');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    // ── Field CRUD ────────────────────────────────────────────────────────────

    public function openCreate(): void
    {
        $this->authorize('system.manage-custom-fields');
        $this->resetFieldForm();
        $this->showFormFor       = 'create';
        $this->manageOptionsFor  = null;
    }

    public function openEdit(string $id): void
    {
        $this->authorize('system.manage-custom-fields');
        $field = CustomField::findOrFail($id);

        $this->formNameAr          = $field->name_ar;
        $this->formNameEn          = $field->name_en;
        $this->formFieldType       = $field->field_type;
        $this->formScopeType       = $field->scope_type;
        $this->formScopeCategoryId = $field->scope_category_id ?? '';
        $this->formIsRequired      = $field->is_required;
        $this->formIsActive        = $field->is_active;
        $this->showFormFor         = $id;
        $this->manageOptionsFor    = null;
    }

    public function save(): void
    {
        $this->authorize('system.manage-custom-fields');
        $this->validate($this->fieldRules());

        $scopeCategoryId = ($this->formScopeType === 'category' && $this->formScopeCategoryId !== '')
            ? $this->formScopeCategoryId
            : null;

        if ($this->showFormFor === 'create') {
            CustomField::create([
                'name_ar'           => $this->formNameAr,
                'name_en'           => $this->formNameEn,
                'field_type'        => $this->formFieldType,
                'scope_type'        => $this->formScopeType,
                'scope_category_id' => $scopeCategoryId,
                'is_required'       => $this->formIsRequired,
                'is_active'         => $this->formIsActive,
                'display_order'     => CustomField::max('display_order') + 1,
                'version'           => 1,
            ]);
            session()->flash('success', __('admin.custom_fields.created'));
        } else {
            $field = CustomField::findOrFail($this->showFormFor);

            // Block field_type change when values exist
            if ($field->field_type !== $this->formFieldType && $field->values()->exists()) {
                $this->addError('formFieldType', __('admin.custom_fields.type_change_blocked'));
                return;
            }

            $field->update([
                'name_ar'           => $this->formNameAr,
                'name_en'           => $this->formNameEn,
                'field_type'        => $this->formFieldType,
                'scope_type'        => $this->formScopeType,
                'scope_category_id' => $scopeCategoryId,
                'is_required'       => $this->formIsRequired,
                'is_active'         => $this->formIsActive,
                'version'           => $field->version + 1,
            ]);
            session()->flash('success', __('admin.custom_fields.updated'));
        }

        $this->resetFieldForm();
    }

    public function toggleActive(string $id): void
    {
        $this->authorize('system.manage-custom-fields');
        $field = CustomField::findOrFail($id);
        $field->update(['is_active' => ! $field->is_active]);

        session()->flash('success', $field->is_active
            ? __('admin.custom_fields.activated')
            : __('admin.custom_fields.deactivated'));
    }

    public function delete(string $id): void
    {
        $this->authorize('system.manage-custom-fields');
        CustomField::findOrFail($id)->delete();
        session()->flash('success', __('admin.custom_fields.deleted'));
        $this->resetPage();
    }

    public function cancelForm(): void
    {
        $this->resetFieldForm();
    }

    // ── Display order reorder ─────────────────────────────────────────────────

    public function reorder(array $orderedIds): void
    {
        $this->authorize('system.manage-custom-fields');

        foreach ($orderedIds as $position => $id) {
            CustomField::where('id', $id)->update(['display_order' => $position]);
        }
    }

    // ── Options panel ─────────────────────────────────────────────────────────

    public function openOptions(string $id): void
    {
        $this->authorize('system.manage-custom-fields');
        $this->manageOptionsFor = $id;
        $this->showFormFor      = null;
        $this->resetOptionForm();
    }

    public function closeOptions(): void
    {
        $this->manageOptionsFor = null;
        $this->resetOptionForm();
    }

    public function openEditOption(string $optionId): void
    {
        $this->authorize('system.manage-custom-fields');
        $option             = CustomFieldOption::findOrFail($optionId);
        $this->editOptionId = $optionId;
        $this->optionValueAr = $option->value_ar;
        $this->optionValueEn = $option->value_en;
    }

    public function saveOption(): void
    {
        $this->authorize('system.manage-custom-fields');
        $this->validate($this->optionRules());

        if ($this->editOptionId === null) {
            $field   = CustomField::findOrFail($this->manageOptionsFor);
            $maxSort = $field->options()->max('sort_order') ?? -1;
            $field->options()->create([
                'value_ar'   => $this->optionValueAr,
                'value_en'   => $this->optionValueEn,
                'sort_order' => $maxSort + 1,
                'is_active'  => true,
            ]);
            session()->flash('option_success', __('admin.custom_fields.option_created'));
        } else {
            $option = CustomFieldOption::findOrFail($this->editOptionId);
            $option->update([
                'value_ar' => $this->optionValueAr,
                'value_en' => $this->optionValueEn,
            ]);
            session()->flash('option_success', __('admin.custom_fields.option_updated'));
        }

        $this->resetOptionForm();
    }

    public function deleteOption(string $optionId): void
    {
        $this->authorize('system.manage-custom-fields');
        CustomFieldOption::findOrFail($optionId)->delete();
        session()->flash('option_success', __('admin.custom_fields.option_deleted'));
    }

    public function reorderOptions(array $orderedIds): void
    {
        $this->authorize('system.manage-custom-fields');

        foreach ($orderedIds as $position => $id) {
            CustomFieldOption::where('id', $id)->update(['sort_order' => $position]);
        }
    }

    public function cancelOption(): void
    {
        $this->resetOptionForm();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resetFieldForm(): void
    {
        $this->showFormFor         = null;
        $this->formNameAr          = '';
        $this->formNameEn          = '';
        $this->formFieldType       = 'text';
        $this->formScopeType       = 'global';
        $this->formScopeCategoryId = '';
        $this->formIsRequired      = false;
        $this->formIsActive        = true;
        $this->resetValidation();
    }

    private function resetOptionForm(): void
    {
        $this->editOptionId  = null;
        $this->optionValueAr = '';
        $this->optionValueEn = '';
        $this->resetValidation();
    }

    public function render()
    {
        $fields = CustomField::withTrashed()
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name_ar', 'like', '%' . $this->search . '%')
                  ->orWhere('name_en', 'like', '%' . $this->search . '%');
            }))
            ->orderBy('display_order')
            ->orderBy('created_at')
            ->paginate(20);

        $categories = Category::active()->orderBy('name_en')->get();

        $activeOptions = $this->manageOptionsFor
            ? CustomFieldOption::withTrashed()
                ->where('custom_field_id', $this->manageOptionsFor)
                ->orderBy('sort_order')
                ->get()
            : collect();

        $optionField = $this->manageOptionsFor
            ? CustomField::find($this->manageOptionsFor)
            : null;

        return view('livewire.admin.custom-fields.custom-field-index', compact(
            'fields', 'categories', 'activeOptions', 'optionField'
        ));
    }
}
