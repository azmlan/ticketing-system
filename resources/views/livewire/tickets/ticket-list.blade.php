<div>
    <div class="mb-6 flex flex-wrap justify-between items-center gap-4">
        <h1 class="text-2xl font-bold">{{ __('tickets.list.title') }}</h1>
        <div class="flex flex-wrap items-center gap-4">
            {{-- SLA Compliance Summary --}}
            @if ($compliance['total'] > 0)
                <div class="flex items-center gap-2 text-sm">
                    <span class="text-gray-500 font-medium">{{ __('sla.compliance.title') }}:</span>
                    <span class="font-semibold text-green-700">
                        {{ __('sla.compliance.rate', ['percent' => $compliance['percent']]) }}
                    </span>
                    @if ($compliance['breached'] > 0)
                        <span class="font-semibold text-red-600">
                            · {{ __('sla.compliance.breached_count', ['count' => $compliance['breached']]) }}
                        </span>
                    @endif
                </div>
            @endif
            <a href="{{ route('tickets.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded">
                {{ __('tickets.list.create_button') }}
            </a>
        </div>
    </div>

    <table class="w-full text-sm border-collapse">
        <thead>
            <tr class="border-b">
                <th class="py-2 ps-3 text-start">{{ __('tickets.list.columns.number') }}</th>
                <th class="py-2 ps-3 text-start">{{ __('tickets.list.columns.subject') }}</th>
                <th class="py-2 ps-3 text-start">{{ __('tickets.list.columns.status') }}</th>
                <th class="py-2 ps-3 text-start">{{ __('tickets.list.columns.category') }}</th>
                <th class="py-2 ps-3 text-start">{{ __('tickets.list.columns.assigned_to') }}</th>
                <th class="py-2 ps-3 text-start">{{ __('sla.column_header') }}</th>
                <th class="py-2 ps-3 text-start">{{ __('tickets.list.columns.created_at') }}</th>
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
                    <td class="py-2 ps-3">{{ $ticket->assignedTo?->full_name ?? __('tickets.list.unassigned') }}</td>
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
                    <td colspan="7" class="py-8 text-center text-gray-500">{{ __('tickets.list.empty') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $tickets->links() }}
    </div>
</div>
