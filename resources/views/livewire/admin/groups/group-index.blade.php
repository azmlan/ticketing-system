<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">{{ __('admin.groups.title') }}</h1>
        <button wire:click="openCreate"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            {{ __('admin.groups.create') }}
        </button>
    </div>

    {{-- Inline form --}}
    @if($showFormFor !== null)
    <div class="mb-6 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-base font-medium text-gray-900 mb-4">
            {{ $showFormFor === 'create' ? __('admin.groups.create') : __('admin.groups.edit') }}
        </h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('admin.groups.name_ar') }} <span class="text-red-500">*</span>
                </label>
                <input wire:model="formNameAr" type="text" dir="rtl"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('formNameAr') border-red-500 @enderror">
                @error('formNameAr') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('admin.groups.name_en') }} <span class="text-red-500">*</span>
                </label>
                <input wire:model="formNameEn" type="text" dir="ltr"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('formNameEn') border-red-500 @enderror">
                @error('formNameEn') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-center gap-3 pt-5">
                <input wire:model="formIsActive" type="checkbox" id="formIsActive"
                       class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                <label for="formIsActive" class="text-sm text-gray-700">{{ __('admin.groups.is_active') }}</label>
            </div>
        </div>
        <div class="flex items-center gap-3 mt-4">
            <button wire:click="save"
                    class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                {{ __('admin.groups.save') }}
            </button>
            <button wire:click="cancelForm"
                    class="px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-md border border-gray-300 hover:bg-gray-50">
                {{ __('admin.groups.cancel') }}
            </button>
        </div>
    </div>
    @endif

    {{-- Search --}}
    <div class="mb-4">
        <input wire:model.live.debounce.300ms="search" type="text"
               placeholder="{{ __('admin.groups.search_placeholder') }}"
               class="w-full max-w-sm border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('admin.groups.name_ar') }} / {{ __('admin.groups.name_en') }}
                    </th>
                    <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('admin.groups.manager') }}
                    </th>
                    <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('admin.groups.members_count') }}
                    </th>
                    <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('admin.groups.is_active') }}
                    </th>
                    <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ __('admin.actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($groups as $group)
                <tr class="{{ $group->trashed() ? 'opacity-50 bg-gray-50' : '' }}">
                    <td class="px-4 py-3">
                        <p class="text-sm font-medium text-gray-900" dir="rtl">{{ $group->name_ar }}</p>
                        <p class="text-xs text-gray-500" dir="ltr">{{ $group->name_en }}</p>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700">
                        {{ $group->manager?->full_name ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500">
                        {{ $group->users_count }}
                    </td>
                    <td class="px-4 py-3">
                        @if($group->trashed())
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                {{ __('admin.delete') }}d
                            </span>
                        @elseif($group->is_active)
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
                        @if(!$group->trashed())
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.groups.members', $group) }}"
                               class="text-xs text-blue-600 hover:text-blue-800">
                                {{ __('admin.groups.members_link') }}
                            </a>
                            <button wire:click="openEdit('{{ $group->id }}')"
                                    class="text-xs text-gray-600 hover:text-gray-900">
                                {{ __('admin.edit') }}
                            </button>
                            <button wire:click="toggleActive('{{ $group->id }}')"
                                    class="text-xs {{ $group->is_active ? 'text-amber-600 hover:text-amber-800' : 'text-green-600 hover:text-green-800' }}">
                                {{ $group->is_active ? __('admin.deactivate') : __('admin.activate') }}
                            </button>
                            <button wire:click="delete('{{ $group->id }}')"
                                    wire:confirm="{{ __('admin.groups.confirm_delete') }}"
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
                        {{ __('admin.groups.no_results') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($groups->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $groups->links() }}
        </div>
        @endif
    </div>
</div>
