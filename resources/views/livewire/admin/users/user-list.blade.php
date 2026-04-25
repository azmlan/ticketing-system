<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.users.title') }}</h1>
    </div>

    {{-- Filters --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <input type="search" wire:model.live.debounce.300ms="search"
               placeholder="{{ __('admin.users.search_placeholder') }}"
               class="block w-full max-w-xs rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">

        <select wire:model.live="filterRole"
                class="rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            <option value="">{{ __('admin.users.filter_all_roles') }}</option>
            <option value="employee">{{ __('admin.users.role_employee') }}</option>
            <option value="tech">{{ __('admin.users.role_tech') }}</option>
            <option value="it_manager">{{ __('admin.users.role_it_manager') }}</option>
        </select>

        <select wire:model.live="filterStatus"
                class="rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            <option value="">{{ __('admin.users.filter_all_status') }}</option>
            <option value="active">{{ __('admin.users.filter_active') }}</option>
            <option value="inactive">{{ __('admin.users.filter_inactive') }}</option>
        </select>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        @if($users->isEmpty())
            <p class="p-6 text-sm text-gray-500">{{ __('admin.users.no_results') }}</p>
        @else
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.users.name') }}</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.users.email') }}</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.users.role') }}</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.users.department') }}</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.users.status') }}</th>
                    <th class="px-4 py-3 text-end font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($users as $user)
                <tr class="{{ $user->deleted_at ? 'bg-red-50' : '' }}">
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $user->full_name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                    <td class="px-4 py-3">
                        @if($user->is_super_user)
                            <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-800">
                                {{ __('admin.users.role_it_manager') }}
                            </span>
                        @elseif($user->is_tech)
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                                {{ __('admin.users.role_tech') }}
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                                {{ __('admin.users.role_employee') }}
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $user->department?->name_en ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @if($user->deleted_at)
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                {{ __('admin.users.filter_inactive') }}
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                {{ __('admin.users.filter_active') }}
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-end">
                        <a href="{{ route('admin.users.show', $user) }}"
                           class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                            {{ __('admin.users.view') }}
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
</div>
