<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.locations.title') }}</h1>
        @if($showFormFor === null)
        <button wire:click="openCreate"
                class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            {{ __('admin.locations.create') }}
        </button>
        @endif
    </div>

    {{-- Inline form --}}
    @if($showFormFor !== null)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 mb-6">
        <h2 class="text-base font-semibold mb-4">
            {{ $showFormFor === 'create' ? __('admin.locations.create') : __('admin.locations.edit') }}
        </h2>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.locations.name_ar') }}</label>
                <input type="text" wire:model="formNameAr" dir="rtl"
                       class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('formNameAr') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.locations.name_en') }}</label>
                <input type="text" wire:model="formNameEn" dir="ltr"
                       class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('formNameEn') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.locations.sort_order') }}</label>
                <input type="number" wire:model="formSortOrder" min="0"
                       class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('formSortOrder') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center gap-2 pt-5">
                <input type="checkbox" wire:model="formIsActive" id="loc_is_active"
                       class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <label for="loc_is_active" class="text-sm text-gray-700">{{ __('admin.locations.is_active') }}</label>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button wire:click="save"
                    class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                {{ __('admin.locations.save') }}
            </button>
            <button wire:click="cancelForm"
                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                {{ __('admin.locations.cancel') }}
            </button>
        </div>
    </div>
    @endif

    {{-- Search --}}
    <div class="mb-4">
        <input type="search" wire:model.live.debounce.300ms="search"
               placeholder="{{ __('admin.locations.search_placeholder') }}"
               class="block w-full max-w-xs rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        @if($locations->isEmpty())
            <p class="p-6 text-sm text-gray-500">{{ __('admin.locations.no_results') }}</p>
        @else
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.locations.name_ar') }}</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.locations.name_en') }}</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.locations.sort_order') }}</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.locations.is_active') }}</th>
                    <th class="px-4 py-3 text-end font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($locations as $loc)
                <tr class="{{ $loc->deleted_at ? 'bg-red-50' : ($loc->is_active ? '' : 'bg-gray-50') }}">
                    <td class="px-4 py-3 font-medium text-gray-900" dir="rtl">{{ $loc->name_ar }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ $loc->name_en }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $loc->sort_order }}</td>
                    <td class="px-4 py-3">
                        @if($loc->deleted_at)
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                {{ __('admin.locations.deleted_label') }}
                            </span>
                        @elseif($loc->is_active)
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                {{ __('admin.status_active') }}
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                                {{ __('admin.status_inactive') }}
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-end">
                        @if(!$loc->deleted_at)
                        <div class="inline-flex items-center gap-2">
                            <button wire:click="openEdit('{{ $loc->id }}')"
                                    class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                {{ __('admin.edit') }}
                            </button>
                            @if($loc->is_active)
                            <button wire:click="toggleActive('{{ $loc->id }}')"
                                    class="text-yellow-600 hover:text-yellow-800 text-xs font-medium">
                                {{ __('admin.deactivate') }}
                            </button>
                            @else
                            <button wire:click="toggleActive('{{ $loc->id }}')"
                                    class="text-green-600 hover:text-green-800 text-xs font-medium">
                                {{ __('admin.activate') }}
                            </button>
                            @endif
                            <button wire:click="delete('{{ $loc->id }}')"
                                    wire:confirm="{{ __('admin.locations.confirm_delete') }}"
                                    class="text-red-600 hover:text-red-800 text-xs font-medium">
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

    <div class="mt-4">
        {{ $locations->links() }}
    </div>
</div>
