<div>
    {{-- ── Header ───────────────────────────────────────────────────────── --}}
    <div class="mb-6 flex flex-wrap justify-between items-center gap-4">
        <h1 class="text-2xl font-bold">{{ __('tickets.dashboard.employee.title') }}</h1>
        <a href="{{ route('tickets.create') }}"
           class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm font-medium">
            {{ __('tickets.dashboard.employee.new_ticket') }}
        </a>
    </div>

    {{-- ── Count Badges ─────────────────────────────────────────────────── --}}
    <div class="mb-6 flex flex-wrap gap-3">
        <div class="flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-lg px-4 py-2">
            <span class="text-sm font-medium text-blue-700">{{ __('tickets.dashboard.employee.badge_open') }}</span>
            <span class="text-lg font-bold text-blue-800">{{ $counts['open'] }}</span>
        </div>
        <div class="flex items-center gap-2 bg-green-50 border border-green-200 rounded-lg px-4 py-2">
            <span class="text-sm font-medium text-green-700">{{ __('tickets.dashboard.employee.badge_resolved') }}</span>
            <span class="text-lg font-bold text-green-800">{{ $counts['resolved'] }}</span>
        </div>
        <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-4 py-2">
            <span class="text-sm font-medium text-gray-600">{{ __('tickets.dashboard.employee.badge_closed') }}</span>
            <span class="text-lg font-bold text-gray-700">{{ $counts['closed'] }}</span>
        </div>
        <div class="flex items-center gap-2 bg-red-50 border border-red-200 rounded-lg px-4 py-2">
            <span class="text-sm font-medium text-red-600">{{ __('tickets.dashboard.employee.badge_cancelled') }}</span>
            <span class="text-lg font-bold text-red-700">{{ $counts['cancelled'] }}</span>
        </div>
    </div>

    {{-- ── Filters ──────────────────────────────────────────────────────── --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        {{-- Status Tabs --}}
        <div class="flex flex-wrap gap-1">
            @foreach ([
                ''          => __('tickets.dashboard.employee.filter_all'),
                'open'      => __('tickets.dashboard.employee.filter_open'),
                'resolved'  => __('tickets.dashboard.employee.filter_resolved'),
                'closed'    => __('tickets.dashboard.employee.filter_closed'),
                'cancelled' => __('tickets.dashboard.employee.filter_cancelled'),
            ] as $value => $label)
                <button
                    wire:click="$set('statusFilter', '{{ $value }}')"
                    class="px-3 py-1.5 rounded text-sm font-medium transition-colors
                        {{ $statusFilter === $value
                            ? 'bg-blue-600 text-white'
                            : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Subject Search --}}
        <div class="flex-1 min-w-48">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('tickets.dashboard.employee.search_placeholder') }}"
                class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
            />
        </div>
    </div>

    {{-- ── Ticket Table ─────────────────────────────────────────────────── --}}
    <table class="w-full text-sm border-collapse">
        <thead>
            <tr class="border-b bg-gray-50">
                <th class="py-2 ps-3 text-start font-medium text-gray-600">{{ __('tickets.list.columns.number') }}</th>
                <th class="py-2 ps-3 text-start font-medium text-gray-600">{{ __('tickets.list.columns.subject') }}</th>
                <th class="py-2 ps-3 text-start font-medium text-gray-600">{{ __('tickets.list.columns.status') }}</th>
                <th class="py-2 ps-3 text-start font-medium text-gray-600">{{ __('tickets.list.columns.category') }}</th>
                <th class="py-2 ps-3 text-start font-medium text-gray-600">{{ __('sla.column_header') }}</th>
                <th class="py-2 ps-3 text-start font-medium text-gray-600">{{ __('tickets.list.columns.created_at') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tickets as $ticket)
                @php $sla = $slaMap->get($ticket->id); @endphp
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-2 ps-3 text-gray-500 font-mono text-xs">{{ $ticket->display_number }}</td>
                    <td class="py-2 ps-3">
                        <a href="{{ route('tickets.show', $ticket) }}" class="text-blue-600 hover:underline">
                            {{ $ticket->subject }}
                        </a>
                    </td>
                    <td class="py-2 ps-3">{{ __('tickets.status.' . $ticket->status->value) }}</td>
                    <td class="py-2 ps-3">{{ $ticket->category?->localizedName() ?? '—' }}</td>
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
                    <td class="py-2 ps-3 text-gray-500">{{ $ticket->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-8 text-center text-gray-500">{{ __('tickets.list.empty') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
