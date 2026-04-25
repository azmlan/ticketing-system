<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">{{ __('admin.response_templates.title') }}</h1>
        <button wire:click="openCreate"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            {{ __('admin.response_templates.create') }}
        </button>
    </div>

    {{-- Inline form --}}
    @if($showFormFor !== null)
    <div class="mb-6 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-base font-medium text-gray-900 mb-4">
            {{ $showFormFor === 'create' ? __('admin.response_templates.create') : __('admin.response_templates.edit') }}
        </h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('admin.response_templates.title_ar') }} <span class="text-red-500">*</span>
                </label>
                <input wire:model="formTitleAr" type="text" dir="rtl"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('formTitleAr') border-red-500 @enderror">
                @error('formTitleAr') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('admin.response_templates.title_en') }} <span class="text-red-500">*</span>
                </label>
                <input wire:model="formTitleEn" type="text" dir="ltr"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('formTitleEn') border-red-500 @enderror">
                @error('formTitleEn') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('admin.response_templates.body_ar') }} <span class="text-red-500">*</span>
                </label>
                <textarea wire:model="formBodyAr" rows="5" dir="rtl"
                          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('formBodyAr') border-red-500 @enderror"></textarea>
                @error('formBodyAr') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('admin.response_templates.body_en') }} <span class="text-red-500">*</span>
                </label>
                <textarea wire:model="formBodyEn" rows="5" dir="ltr"
                          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('formBodyEn') border-red-500 @enderror"></textarea>
                @error('formBodyEn') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-center gap-6 sm:col-span-2">
                <div class="flex items-center gap-2">
                    <input wire:model="formIsInternal" type="checkbox" id="formIsInternal"
                           class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                    <label for="formIsInternal" class="text-sm text-gray-700">{{ __('admin.response_templates.is_internal') }}</label>
                </div>
                <div class="flex items-center gap-2">
                    <input wire:model="formIsActive" type="checkbox" id="formIsActive"
                           class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                    <label for="formIsActive" class="text-sm text-gray-700">{{ __('admin.response_templates.is_active') }}</label>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3 mt-4">
            <button wire:click="save"
                    class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                {{ __('admin.response_templates.save') }}
            </button>
            <button wire:click="cancelForm"
                    class="px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-md border border-gray-300 hover:bg-gray-50">
                {{ __('admin.response_templates.cancel') }}
            </button>
        </div>
    </div>
    @endif

    {{-- Filters --}}
    <div class="mb-4 flex items-center gap-4">
        <input wire:model.live.debounce.300ms="search" type="text"
               placeholder="{{ __('admin.response_templates.search_placeholder') }}"
               class="w-full max-w-sm border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        <select wire:model.live="filterInternal"
                class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="">{{ __('admin.response_templates.filter_all') }}</option>
            <option value="1">{{ __('admin.response_templates.filter_internal') }}</option>
            <option value="0">{{ __('admin.response_templates.filter_public') }}</option>
        </select>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('admin.response_templates.title_ar') }} / {{ __('admin.response_templates.title_en') }}
                    </th>
                    <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('admin.response_templates.is_internal') }}
                    </th>
                    <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('admin.response_templates.is_active') }}
                    </th>
                    <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('admin.actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($templates as $template)
                <tr class="{{ $template->trashed() ? 'opacity-50 bg-gray-50' : '' }}">
                    <td class="px-4 py-3">
                        <p class="text-sm font-medium text-gray-900" dir="rtl">{{ $template->title_ar }}</p>
                        <p class="text-xs text-gray-500" dir="ltr">{{ $template->title_en }}</p>
                    </td>
                    <td class="px-4 py-3">
                        @if($template->is_internal)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                {{ __('admin.response_templates.filter_internal') }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                {{ __('admin.response_templates.filter_public') }}
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($template->trashed())
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                {{ __('admin.delete') }}d
                            </span>
                        @elseif($template->is_active)
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
                        @if(!$template->trashed())
                        <div class="flex items-center justify-end gap-2">
                            <button wire:click="openEdit('{{ $template->id }}')"
                                    class="text-xs text-gray-600 hover:text-gray-900">
                                {{ __('admin.edit') }}
                            </button>
                            <button wire:click="toggleActive('{{ $template->id }}')"
                                    class="text-xs {{ $template->is_active ? 'text-amber-600 hover:text-amber-800' : 'text-green-600 hover:text-green-800' }}">
                                {{ $template->is_active ? __('admin.deactivate') : __('admin.activate') }}
                            </button>
                            <button wire:click="delete('{{ $template->id }}')"
                                    wire:confirm="{{ __('admin.response_templates.confirm_delete') }}"
                                    class="text-xs text-red-600 hover:text-red-800">
                                {{ __('admin.delete') }}
                            </button>
                        </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">
                        {{ __('admin.response_templates.no_results') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($templates->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $templates->links() }}
        </div>
        @endif
    </div>
</div>
