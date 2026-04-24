<div>
    {{-- ── Quick Stats ──────────────────────────────────────────────────────── --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold mb-4">{{ __('tickets.dashboard.tech.title') }}</h1>
        <div class="flex flex-wrap gap-3">
            <div class="flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-lg px-4 py-2">
                <span class="text-sm font-medium text-blue-700">{{ __('tickets.dashboard.tech.stat_open') }}</span>
                <span class="text-lg font-bold text-blue-800">{{ $stats['open'] }}</span>
            </div>
            <div class="flex items-center gap-2 bg-green-50 border border-green-200 rounded-lg px-4 py-2">
                <span class="text-sm font-medium text-green-700">{{ __('tickets.dashboard.tech.stat_resolved_week') }}</span>
                <span class="text-lg font-bold text-green-800">{{ $stats['resolved_week'] }}</span>
            </div>
            <div class="flex items-center gap-2 bg-teal-50 border border-teal-200 rounded-lg px-4 py-2">
                <span class="text-sm font-medium text-teal-700">{{ __('tickets.dashboard.tech.stat_resolved_month') }}</span>
                <span class="text-lg font-bold text-teal-800">{{ $stats['resolved_month'] }}</span>
            </div>
            <div class="flex items-center gap-2 bg-purple-50 border border-purple-200 rounded-lg px-4 py-2">
                <span class="text-sm font-medium text-purple-700">{{ __('tickets.dashboard.tech.stat_sla_compliance') }}</span>
                <span class="text-lg font-bold text-purple-800">{{ $stats['sla_compliance'] }}%</span>
            </div>
        </div>
    </div>

    {{-- ── Filter Bar ───────────────────────────────────────────────────────── --}}
    <div class="mb-6 border border-gray-200 rounded-lg bg-gray-50 p-4">
        <h2 class="text-sm font-semibold text-gray-700 mb-3">{{ __('tickets.dashboard.tech.filter_title') }}</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">

            {{-- Free-text search --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('tickets.dashboard.tech.filter_search') }}</label>
                <input type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('tickets.dashboard.tech.filter_search_placeholder') }}"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400" />
            </div>

            {{-- Category --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('tickets.dashboard.tech.filter_category') }}</label>
                <select wire:model.live="filterCategory"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400">
                    <option value="">{{ __('tickets.dashboard.tech.filter_any') }}</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->localizedName() }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Subcategory (dependent) --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('tickets.dashboard.tech.filter_subcategory') }}</label>
                <select wire:model.live="filterSubcategory"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400"
                    @if ($filterCategory === '') disabled @endif>
                    <option value="">{{ __('tickets.dashboard.tech.filter_any') }}</option>
                    @foreach ($subcategories as $sub)
                        <option value="{{ $sub->id }}">{{ $sub->localizedName() }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Assigned To --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('tickets.dashboard.tech.filter_assigned_to') }}</label>
                <select wire:model.live="filterAssignedTo"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400">
                    <option value="">{{ __('tickets.dashboard.tech.filter_any') }}</option>
                    <option value="unassigned">{{ __('tickets.list.unassigned') }}</option>
                    @foreach ($techUsers as $tech)
                        <option value="{{ $tech->id }}">{{ $tech->full_name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Date From --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('tickets.dashboard.tech.filter_date_from') }}</label>
                <input type="date" wire:model.live="dateFrom"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400" />
            </div>

            {{-- Date To --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('tickets.dashboard.tech.filter_date_to') }}</label>
                <input type="date" wire:model.live="dateTo"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400" />
            </div>

            {{-- Sort By --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('tickets.dashboard.tech.sort_by') }}</label>
                <select wire:model.live="sortBy"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400">
                    <option value="">{{ __('tickets.dashboard.tech.sort_default') }}</option>
                    <option value="created_at">{{ __('tickets.dashboard.tech.sort_created_at') }}</option>
                    <option value="priority">{{ __('tickets.dashboard.tech.sort_priority') }}</option>
                    <option value="updated_at">{{ __('tickets.dashboard.tech.sort_updated_at') }}</option>
                </select>
            </div>

            {{-- Sort Direction --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('tickets.dashboard.tech.sort_dir') }}</label>
                <select wire:model.live="sortDir"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400">
                    <option value="desc">{{ __('tickets.dashboard.tech.sort_desc') }}</option>
                    <option value="asc">{{ __('tickets.dashboard.tech.sort_asc') }}</option>
                </select>
            </div>
        </div>

        {{-- Multi-select row: Status, Priority, Group --}}
        <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3">

            {{-- Status multi --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('tickets.dashboard.tech.filter_status') }}</label>
                <div class="flex flex-wrap gap-1">
                    @foreach (['awaiting_assignment','in_progress','on_hold','resolved','closed','cancelled'] as $s)
                        <label class="inline-flex items-center gap-1 text-xs cursor-pointer">
                            <input type="checkbox" wire:model.live="filterStatus" value="{{ $s }}"
                                class="rounded border-gray-300 text-blue-600" />
                            {{ __('tickets.status.' . $s) }}
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Priority multi --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('tickets.dashboard.tech.filter_priority') }}</label>
                <div class="flex flex-wrap gap-1">
                    @foreach (['critical','high','medium','low'] as $p)
                        <label class="inline-flex items-center gap-1 text-xs cursor-pointer">
                            <input type="checkbox" wire:model.live="filterPriority" value="{{ $p }}"
                                class="rounded border-gray-300 text-blue-600" />
                            {{ __('tickets.priority.' . $p) }}
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Group multi (persisted) --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('tickets.dashboard.tech.filter_groups') }}</label>
                <div class="flex flex-wrap gap-1 max-h-24 overflow-y-auto">
                    @foreach ($groups as $group)
                        <label class="inline-flex items-center gap-1 text-xs cursor-pointer">
                            <input type="checkbox" wire:model.live="filterGroups" value="{{ $group->id }}"
                                class="rounded border-gray-300 text-blue-600" />
                            {{ $group->localizedName() }}
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ── Pending Transfer Requests ────────────────────────────────────────── --}}
    @if ($pendingTransfers->isNotEmpty())
        <div class="mb-6 border border-yellow-200 rounded-lg bg-yellow-50 p-4">
            <h2 class="text-base font-semibold text-yellow-800 mb-3">
                {{ __('tickets.dashboard.tech.transfer_panel_title') }}
                <span class="ms-1 text-sm font-normal text-yellow-700">({{ $pendingTransfers->count() }})</span>
            </h2>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-yellow-200">
                        <th class="py-1.5 ps-2 text-start font-medium text-yellow-700">{{ __('tickets.list.columns.number') }}</th>
                        <th class="py-1.5 ps-2 text-start font-medium text-yellow-700">{{ __('tickets.list.columns.subject') }}</th>
                        <th class="py-1.5 ps-2 text-start font-medium text-yellow-700">{{ __('tickets.dashboard.tech.transfer_from') }}</th>
                        <th class="py-1.5 ps-2 text-start font-medium text-yellow-700">{{ __('tickets.dashboard.tech.transfer_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pendingTransfers as $tr)
                        <tr class="border-b border-yellow-100">
                            <td class="py-1.5 ps-2 font-mono text-xs text-gray-600">{{ $tr->ticket->display_number }}</td>
                            <td class="py-1.5 ps-2">
                                <a href="{{ route('tickets.show', $tr->ticket) }}" class="text-blue-600 hover:underline">
                                    {{ $tr->ticket->subject }}
                                </a>
                            </td>
                            <td class="py-1.5 ps-2 text-gray-600">{{ $tr->fromUser->full_name }}</td>
                            <td class="py-1.5 ps-2 flex gap-2">
                                <button wire:click="acceptTransfer('{{ $tr->id }}')"
                                    class="px-2 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700">
                                    {{ __('tickets.show.actions.accept_transfer') }}
                                </button>
                                <button wire:click="declineTransfer('{{ $tr->id }}')"
                                    class="px-2 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700">
                                    {{ __('tickets.show.actions.reject_transfer') }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- ── Two-column layout: Queue + My Tickets ───────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Queue --}}
        <div>
            <h2 class="text-base font-semibold text-gray-800 mb-3">
                {{ __('tickets.dashboard.tech.queue_title') }}
                <span class="ms-1 text-sm font-normal text-gray-500">({{ $queueTickets->total() }})</span>
            </h2>
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="border-b bg-gray-50">
                        <th class="py-2 ps-3 text-start font-medium text-gray-600">{{ __('tickets.list.columns.number') }}</th>
                        <th class="py-2 ps-3 text-start font-medium text-gray-600">{{ __('tickets.list.columns.subject') }}</th>
                        <th class="py-2 ps-3 text-start font-medium text-gray-600">{{ __('tickets.list.columns.status') }}</th>
                        <th class="py-2 ps-3 text-start font-medium text-gray-600">{{ __('sla.column_header') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($queueTickets as $ticket)
                        @php $sla = $slaMap->get($ticket->id); @endphp
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-2 ps-3 text-gray-500 font-mono text-xs">{{ $ticket->display_number }}</td>
                            <td class="py-2 ps-3">
                                <a href="{{ route('tickets.show', $ticket) }}" class="text-blue-600 hover:underline">
                                    {{ $ticket->subject }}
                                </a>
                            </td>
                            <td class="py-2 ps-3">{{ __('tickets.status.' . $ticket->status->value) }}</td>
                            <td class="py-2 ps-3">
                                @if ($sla)
                                    <div class="flex flex-wrap gap-1">
                                        <x-sla-status-badge :status="$sla->response_status" type="response" />
                                        <x-sla-status-badge :status="$sla->resolution_status" type="resolution" />
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-gray-500">{{ __('tickets.list.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-3">{{ $queueTickets->links() }}</div>
        </div>

        {{-- My Tickets --}}
        <div>
            <h2 class="text-base font-semibold text-gray-800 mb-3">
                {{ __('tickets.dashboard.tech.my_tickets_title') }}
                <span class="ms-1 text-sm font-normal text-gray-500">({{ $myTickets->total() }})</span>
            </h2>
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="border-b bg-gray-50">
                        <th class="py-2 ps-3 text-start font-medium text-gray-600">{{ __('tickets.list.columns.number') }}</th>
                        <th class="py-2 ps-3 text-start font-medium text-gray-600">{{ __('tickets.list.columns.subject') }}</th>
                        <th class="py-2 ps-3 text-start font-medium text-gray-600">{{ __('tickets.list.columns.status') }}</th>
                        <th class="py-2 ps-3 text-start font-medium text-gray-600">{{ __('sla.column_header') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($myTickets as $ticket)
                        @php $sla = $slaMap->get($ticket->id); @endphp
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-2 ps-3 text-gray-500 font-mono text-xs">{{ $ticket->display_number }}</td>
                            <td class="py-2 ps-3">
                                <a href="{{ route('tickets.show', $ticket) }}" class="text-blue-600 hover:underline">
                                    {{ $ticket->subject }}
                                </a>
                            </td>
                            <td class="py-2 ps-3">{{ __('tickets.status.' . $ticket->status->value) }}</td>
                            <td class="py-2 ps-3">
                                @if ($sla)
                                    <div class="flex flex-wrap gap-1">
                                        <x-sla-status-badge :status="$sla->response_status" type="response" />
                                        <x-sla-status-badge :status="$sla->resolution_status" type="resolution" />
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-gray-500">{{ __('tickets.list.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-3">{{ $myTickets->links() }}</div>
        </div>

    </div>
</div>
