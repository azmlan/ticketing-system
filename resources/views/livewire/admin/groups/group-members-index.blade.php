<div>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('admin.groups.index') }}"
               class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
                {{ __('admin.groups.back') }}
            </a>
            <h1 class="text-xl font-semibold text-gray-900">
                {{ __('admin.groups.members_title') }}: {{ $group->localizedName() }}
            </h1>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- ── Current Members ──────────────────────────────────────────────── --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-4 py-3 border-b border-gray-200">
                <h2 class="text-sm font-medium text-gray-900">{{ __('admin.groups.current_members') }}</h2>
            </div>
            <ul class="divide-y divide-gray-100">
                @forelse($members as $member)
                <li class="flex items-center justify-between px-4 py-3">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $member->full_name }}</p>
                        <p class="text-xs text-gray-500">{{ $member->email }}</p>
                    </div>
                    @can('group.manage-members')
                    <button wire:click="removeMember('{{ $member->id }}')"
                            wire:confirm="{{ __('admin.groups.confirm_remove_member') }}"
                            class="text-xs text-red-600 hover:text-red-800">
                        {{ __('admin.groups.remove') }}
                    </button>
                    @endcan
                </li>
                @empty
                <li class="px-4 py-6 text-center text-sm text-gray-500">
                    {{ __('admin.groups.no_members') }}
                </li>
                @endforelse
            </ul>
        </div>

        <div class="space-y-6">

            {{-- ── Add Tech ─────────────────────────────────────────────────── --}}
            @can('group.manage-members')
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h2 class="text-sm font-medium text-gray-900">{{ __('admin.groups.add_member') }}</h2>
                </div>
                <div class="p-4">
                    <input wire:model.live.debounce.300ms="techSearch" type="text"
                           placeholder="{{ __('admin.groups.search_techs') }}"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm mb-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <ul class="divide-y divide-gray-100 max-h-48 overflow-y-auto">
                        @forelse($availableTechs as $tech)
                        <li class="flex items-center justify-between py-2">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $tech->full_name }}</p>
                                <p class="text-xs text-gray-500">{{ $tech->email }}</p>
                            </div>
                            <button wire:click="addMember('{{ $tech->id }}')"
                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                {{ __('admin.groups.add') }}
                            </button>
                        </li>
                        @empty
                        <li class="py-4 text-center text-sm text-gray-500">
                            {{ __('admin.groups.no_techs_available') }}
                        </li>
                        @endforelse
                    </ul>
                </div>
            </div>
            @endcan

            {{-- ── Group Manager ────────────────────────────────────────────── --}}
            @can('group.manage-manager')
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h2 class="text-sm font-medium text-gray-900">{{ __('admin.groups.manager_title') }}</h2>
                </div>
                <div class="p-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('admin.groups.manager_label') }}
                    </label>
                    <select wire:model="selectedManagerId"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">{{ __('admin.groups.no_manager') }}</option>
                        @foreach($members as $member)
                            <option value="{{ $member->id }}">{{ $member->full_name }}</option>
                        @endforeach
                    </select>
                    <button wire:click="saveManager"
                            class="mt-3 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                        {{ __('admin.groups.save_manager') }}
                    </button>
                    <p class="mt-2 text-xs text-gray-500">{{ __('admin.groups.manager_hint') }}</p>
                </div>
            </div>
            @endcan

        </div>
    </div>
</div>
