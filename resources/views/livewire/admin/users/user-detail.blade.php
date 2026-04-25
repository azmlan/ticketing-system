<div>
    {{-- Back link --}}
    <div class="mb-4">
        <a href="{{ route('admin.users.index') }}"
           class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            {{ __('admin.users.back_to_list') }}
        </a>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ $targetUser->full_name }}</h1>

    {{-- Profile --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 mb-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">{{ __('admin.users.profile_section') }}</h2>
        <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase">{{ __('admin.users.email') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $targetUser->email }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase">{{ __('admin.users.role') }}</dt>
                <dd class="mt-1">
                    @if($targetUser->is_super_user)
                        <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-800">
                            {{ __('admin.users.role_it_manager') }}
                        </span>
                    @elseif($targetUser->is_tech)
                        <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                            {{ __('admin.users.role_tech') }}
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                            {{ __('admin.users.role_employee') }}
                        </span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase">{{ __('admin.users.department') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $targetUser->department?->name_en ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase">{{ __('admin.users.location') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $targetUser->location?->name_en ?? '—' }}</dd>
            </div>
            @if($targetUser->phone)
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase">{{ __('admin.users.phone') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $targetUser->phone }}</dd>
            </div>
            @endif
            @if($targetUser->employee_number)
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase">{{ __('admin.users.employee_number') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $targetUser->employee_number }}</dd>
            </div>
            @endif
        </dl>
    </div>

    {{-- Tech Profile --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 mb-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">{{ __('admin.users.tech_profile_section') }}</h2>
        @if($targetUser->techProfile)
            <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase">{{ __('admin.users.specialization') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $targetUser->techProfile->specialization ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase">{{ __('admin.users.job_title_en') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $targetUser->techProfile->job_title_en ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase">{{ __('admin.users.promoted_at') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $targetUser->techProfile->promoted_at?->format('Y-m-d') ?? '—' }}
                    </dd>
                </div>
            </dl>
        @else
            <p class="text-sm text-gray-500">{{ __('admin.users.no_tech_profile') }}</p>
        @endif
    </div>

    {{-- Promote to Tech --}}
    @if($canPromote && !$targetUser->is_tech && !$targetUser->deleted_at)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 mb-6">
        <h2 class="text-base font-semibold text-gray-900 mb-3">{{ __('admin.users.promote_section') }}</h2>

        @if(!$showPromoteConfirm)
            <button wire:click="confirmPromote"
                    class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                {{ __('admin.users.promote_button') }}
            </button>
        @else
            <div class="rounded-md bg-yellow-50 border border-yellow-200 p-4">
                <p class="text-sm text-yellow-800 mb-3">{{ __('admin.users.promote_confirm') }}</p>
                <div class="flex gap-3">
                    <button wire:click="promote"
                            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                        {{ __('admin.users.promote_button') }}
                    </button>
                    <button wire:click="cancelPromote"
                            class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                        {{ __('admin.users.cancel') }}
                    </button>
                </div>
            </div>
        @endif
    </div>
    @endif

    {{-- IT Manager note --}}
    @if($targetUser->is_super_user)
    <div class="rounded-md bg-blue-50 border border-blue-200 p-4 mb-6">
        <p class="text-sm text-blue-800">{{ __('admin.users.it_manager_note') }}</p>
    </div>
    @endif

    {{-- Permissions --}}
    @if($canManagePerms)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 mb-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">{{ __('admin.users.permissions_section') }}</h2>

        @if($targetUser->is_super_user)
            <p class="text-sm text-gray-500 italic">{{ __('admin.users.permissions_blocked') }}</p>
        @else
            <div class="space-y-6">
                @foreach($permissions as $groupKey => $groupPerms)
                <div>
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        {{ $groupKey }}
                    </h3>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @foreach($groupPerms as $perm)
                        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                            <input type="checkbox"
                                   wire:model="selectedPermissions"
                                   value="{{ $perm->id }}"
                                   class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            {{ app()->getLocale() === 'ar' ? $perm->name_ar : $perm->name_en }}
                            <span class="text-xs text-gray-400 font-mono">({{ $perm->key }})</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>

            <div class="mt-4">
                <button wire:click="savePermissions"
                        class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                    {{ __('admin.users.save_permissions') }}
                </button>
            </div>
        @endif
    </div>
    @endif
</div>
