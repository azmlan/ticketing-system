<div>
    <div class="mb-4">
        <a href="{{ route('admin.categories.index') }}"
           class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            {{ __('admin.subcategories.back') }}
        </a>
    </div>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">{{ __('admin.subcategories.title') }}</h1>
            <p class="text-sm text-gray-500 mt-0.5" dir="rtl">{{ $category->name_ar }}</p>
        </div>
        <button wire:click="openCreate"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            {{ __('admin.subcategories.create') }}
        </button>
    </div>

    {{-- Inline form --}}
    @if($showFormFor !== null)
    <div class="mb-6 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-base font-medium text-gray-900 mb-4">
            {{ $showFormFor === 'create' ? __('admin.subcategories.create') : __('admin.subcategories.edit') }}
        </h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('admin.subcategories.name_ar') }} <span class="text-red-500">*</span>
                </label>
                <input wire:model="formNameAr" type="text" dir="rtl"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('formNameAr') border-red-500 @enderror">
                @error('formNameAr') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('admin.subcategories.name_en') }} <span class="text-red-500">*</span>
                </label>
                <input wire:model="formNameEn" type="text" dir="ltr"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('formNameEn') border-red-500 @enderror">
                @error('formNameEn') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-center gap-3">
                <input wire:model="formIsRequired" type="checkbox" id="formIsRequired"
                       class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                <div>
                    <label for="formIsRequired" class="text-sm text-gray-700">{{ __('admin.subcategories.is_required') }}</label>
                    <p class="text-xs text-gray-400">{{ __('admin.subcategories.is_required_hint') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <input wire:model="formIsActive" type="checkbox" id="formIsActive"
                       class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                <label for="formIsActive" class="text-sm text-gray-700">{{ __('admin.subcategories.is_active') }}</label>
            </div>
        </div>
        <div class="flex items-center gap-3 mt-4">
            <button wire:click="save"
                    class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                {{ __('admin.subcategories.save') }}
            </button>
            <button wire:click="cancelForm"
                    class="px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-md border border-gray-300 hover:bg-gray-50">
                {{ __('admin.subcategories.cancel') }}
            </button>
        </div>
    </div>
    @endif

    {{-- Search --}}
    <div class="mb-4">
        <input wire:model.live.debounce.300ms="search" type="text"
               placeholder="{{ __('admin.subcategories.search_placeholder') }}"
               class="w-full max-w-sm border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('admin.subcategories.name_ar') }} / {{ __('admin.subcategories.name_en') }}
                    </th>
                    <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('admin.subcategories.is_required') }}
                    </th>
                    <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('admin.subcategories.version') }}
                    </th>
                    <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('admin.subcategories.is_active') }}
                    </th>
                    <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('admin.actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($subcategories as $sub)
                <tr class="{{ $sub->trashed() ? 'opacity-50 bg-gray-50' : '' }}">
                    <td class="px-4 py-3">
                        <p class="text-sm font-medium text-gray-900" dir="rtl">{{ $sub->name_ar }}</p>
                        <p class="text-xs text-gray-500" dir="ltr">{{ $sub->name_en }}</p>
                    </td>
                    <td class="px-4 py-3 text-sm">
                        @if($sub->is_required)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                {{ __('admin.subcategories.is_required') }}
                            </span>
                        @else
                            <span class="text-gray-400 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500">
                        v{{ $sub->version }}
                    </td>
                    <td class="px-4 py-3">
                        @if($sub->trashed())
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                {{ __('admin.delete') }}d
                            </span>
                        @elseif($sub->is_active)
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
                        @if(!$sub->trashed())
                        <div class="flex items-center justify-end gap-2">
                            <button wire:click="openEdit('{{ $sub->id }}')"
                                    class="text-xs text-gray-600 hover:text-gray-900">
                                {{ __('admin.edit') }}
                            </button>
                            <button wire:click="toggleActive('{{ $sub->id }}')"
                                    class="text-xs {{ $sub->is_active ? 'text-amber-600 hover:text-amber-800' : 'text-green-600 hover:text-green-800' }}">
                                {{ $sub->is_active ? __('admin.deactivate') : __('admin.activate') }}
                            </button>
                            <button wire:click="delete('{{ $sub->id }}')"
                                    wire:confirm="{{ __('admin.subcategories.confirm_delete') }}"
                                    class="text-xs text-red-600 hover:text-red-800">
                                {{ __('admin.delete') }}
                            </button>
                        </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">
                        {{ __('admin.subcategories.no_results') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($subcategories->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $subcategories->links() }}
        </div>
        @endif
    </div>
</div>
