<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">{{ __('admin.custom_fields.title') }}</h1>
        <button wire:click="openCreate"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            {{ __('admin.custom_fields.create') }}
        </button>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-md text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Field form --}}
    @if($showFormFor !== null)
    <div class="mb-6 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-base font-medium text-gray-900 mb-4">
            {{ $showFormFor === 'create' ? __('admin.custom_fields.create') : __('admin.custom_fields.edit') }}
        </h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            {{-- Name AR --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('admin.custom_fields.name_ar') }} <span class="text-red-500">*</span>
                </label>
                <input wire:model="formNameAr" type="text" dir="rtl"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 @error('formNameAr') border-red-500 @enderror">
                @error('formNameAr') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Name EN --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('admin.custom_fields.name_en') }} <span class="text-red-500">*</span>
                </label>
                <input wire:model="formNameEn" type="text" dir="ltr"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 @error('formNameEn') border-red-500 @enderror">
                @error('formNameEn') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Field Type --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('admin.custom_fields.field_type') }} <span class="text-red-500">*</span>
                </label>
                <select wire:model.live="formFieldType"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 @error('formFieldType') border-red-500 @enderror">
                    <option value="text">{{ __('admin.custom_fields.type_text') }}</option>
                    <option value="number">{{ __('admin.custom_fields.type_number') }}</option>
                    <option value="dropdown">{{ __('admin.custom_fields.type_dropdown') }}</option>
                    <option value="multi_select">{{ __('admin.custom_fields.type_multi_select') }}</option>
                    <option value="date">{{ __('admin.custom_fields.type_date') }}</option>
                    <option value="checkbox">{{ __('admin.custom_fields.type_checkbox') }}</option>
                </select>
                @error('formFieldType') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Scope Type --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('admin.custom_fields.scope_type') }} <span class="text-red-500">*</span>
                </label>
                <select wire:model.live="formScopeType"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 @error('formScopeType') border-red-500 @enderror">
                    <option value="global">{{ __('admin.custom_fields.scope_global') }}</option>
                    <option value="category">{{ __('admin.custom_fields.scope_category') }}</option>
                </select>
                @error('formScopeType') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Category (only when scope = category) --}}
            @if($formScopeType === 'category')
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('admin.custom_fields.scope_category') }} <span class="text-red-500">*</span>
                </label>
                <select wire:model="formScopeCategoryId"
                        class="w-full max-w-sm border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 @error('formScopeCategoryId') border-red-500 @enderror">
                    <option value="">{{ __('admin.custom_fields.select_category') }}</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->localizedName() }}</option>
                    @endforeach
                </select>
                @error('formScopeCategoryId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            @endif

            {{-- Checkboxes --}}
            <div class="flex items-center gap-6 pt-1">
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input wire:model="formIsRequired" type="checkbox" class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                    {{ __('admin.custom_fields.is_required') }}
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input wire:model="formIsActive" type="checkbox" class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                    {{ __('admin.custom_fields.is_active') }}
                </label>
            </div>
        </div>

        <div class="flex items-center gap-3 mt-4">
            <button wire:click="save"
                    class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                {{ __('admin.custom_fields.save') }}
            </button>
            <button wire:click="cancelForm"
                    class="px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-md border border-gray-300 hover:bg-gray-50">
                {{ __('admin.custom_fields.cancel') }}
            </button>
        </div>
    </div>
    @endif

    {{-- Options panel --}}
    @if($manageOptionsFor !== null && $optionField !== null)
    <div class="mb-6 bg-white rounded-lg shadow-sm border border-blue-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-medium text-gray-900">
                {{ __('admin.custom_fields.options_title') }} — {{ $optionField->localizedName() }}
            </h2>
            <button wire:click="closeOptions"
                    class="text-sm text-gray-500 hover:text-gray-700">
                {{ __('admin.custom_fields.close_options') }}
            </button>
        </div>

        @if(session('option_success'))
            <div class="mb-3 p-2 bg-green-50 border border-green-200 rounded text-xs text-green-800">
                {{ session('option_success') }}
            </div>
        @endif

        {{-- Option form --}}
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 mb-4">
            <div>
                <input wire:model="optionValueAr" type="text" dir="rtl"
                       placeholder="{{ __('admin.custom_fields.option_value_ar') }}"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 @error('optionValueAr') border-red-500 @enderror">
                @error('optionValueAr') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <input wire:model="optionValueEn" type="text" dir="ltr"
                       placeholder="{{ __('admin.custom_fields.option_value_en') }}"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 @error('optionValueEn') border-red-500 @enderror">
                @error('optionValueEn') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="saveOption"
                        class="px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                    {{ $editOptionId ? __('admin.custom_fields.save') : __('admin.custom_fields.add_option') }}
                </button>
                @if($editOptionId)
                <button wire:click="cancelOption"
                        class="px-3 py-2 bg-white text-gray-700 text-sm font-medium rounded-md border border-gray-300 hover:bg-gray-50">
                    {{ __('admin.custom_fields.cancel') }}
                </button>
                @endif
            </div>
        </div>

        {{-- Options list --}}
        @if($activeOptions->isEmpty())
            <p class="text-sm text-gray-500">{{ __('admin.custom_fields.no_options') }}</p>
        @else
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <tbody class="divide-y divide-gray-100">
                @foreach($activeOptions as $opt)
                <tr class="{{ $opt->trashed() ? 'opacity-40 bg-gray-50' : '' }}">
                    <td class="py-2 pe-4" dir="rtl">{{ $opt->value_ar }}</td>
                    <td class="py-2 pe-4" dir="ltr">{{ $opt->value_en }}</td>
                    <td class="py-2 text-end">
                        @if(!$opt->trashed())
                        <div class="flex items-center justify-end gap-3">
                            <button wire:click="openEditOption('{{ $opt->id }}')"
                                    class="text-xs text-gray-600 hover:text-gray-900">
                                {{ __('admin.custom_fields.edit_option') }}
                            </button>
                            <button wire:click="deleteOption('{{ $opt->id }}')"
                                    wire:confirm="{{ __('admin.custom_fields.confirm_delete_option') }}"
                                    class="text-xs text-red-600 hover:text-red-800">
                                {{ __('admin.delete') }}
                            </button>
                        </div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
    @endif

    {{-- Search --}}
    <div class="mb-4">
        <input wire:model.live.debounce.300ms="search" type="text"
               placeholder="{{ __('admin.custom_fields.search_placeholder') }}"
               class="w-full max-w-sm border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('admin.custom_fields.name_ar') }} / {{ __('admin.custom_fields.name_en') }}
                    </th>
                    <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('admin.custom_fields.field_type') }}
                    </th>
                    <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('admin.custom_fields.scope_type') }}
                    </th>
                    <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('admin.custom_fields.version') }}
                    </th>
                    <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('admin.custom_fields.is_active') }}
                    </th>
                    <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('admin.actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($fields as $field)
                <tr class="{{ $field->trashed() ? 'opacity-50 bg-gray-50' : '' }}">
                    <td class="px-4 py-3">
                        <p class="text-sm font-medium text-gray-900" dir="rtl">{{ $field->name_ar }}</p>
                        <p class="text-xs text-gray-500" dir="ltr">{{ $field->name_en }}</p>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700">
                        {{ __('admin.custom_fields.type_' . $field->field_type) }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700">
                        {{ $field->scope_type === 'global'
                            ? __('admin.custom_fields.scope_global')
                            : ($field->category?->localizedName() ?? __('admin.custom_fields.scope_category')) }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500">
                        v{{ $field->version }}
                    </td>
                    <td class="px-4 py-3">
                        @if($field->trashed())
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                {{ __('admin.delete') }}d
                            </span>
                        @elseif($field->is_active)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                {{ __('admin.status_active') }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                                {{ __('admin.status_inactive') }}
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-end">
                        @if(!$field->trashed())
                        <div class="flex items-center justify-end gap-2">
                            @if(in_array($field->field_type, ['dropdown', 'multi_select']))
                            <button wire:click="openOptions('{{ $field->id }}')"
                                    class="text-xs text-indigo-600 hover:text-indigo-800">
                                {{ __('admin.custom_fields.manage_options') }}
                            </button>
                            @endif
                            <button wire:click="openEdit('{{ $field->id }}')"
                                    class="text-xs text-gray-600 hover:text-gray-900">
                                {{ __('admin.edit') }}
                            </button>
                            <button wire:click="toggleActive('{{ $field->id }}')"
                                    class="text-xs {{ $field->is_active ? 'text-amber-600 hover:text-amber-800' : 'text-green-600 hover:text-green-800' }}">
                                {{ $field->is_active ? __('admin.deactivate') : __('admin.activate') }}
                            </button>
                            <button wire:click="delete('{{ $field->id }}')"
                                    wire:confirm="{{ __('admin.custom_fields.confirm_delete') }}"
                                    class="text-xs text-red-600 hover:text-red-800">
                                {{ __('admin.delete') }}
                            </button>
                        </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">
                        {{ __('admin.custom_fields.no_results') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($fields->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $fields->links() }}
        </div>
        @endif
    </div>
</div>
