<div>
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold">{{ __('tickets.list.title') }}</h1>
        <a href="{{ route('tickets.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded">
            {{ __('tickets.list.create_button') }}
        </a>
    </div>

    <table class="w-full text-sm border-collapse">
        <thead>
            <tr class="border-b">
                <th class="py-2 ps-3 text-start">{{ __('tickets.list.columns.number') }}</th>
                <th class="py-2 ps-3 text-start">{{ __('tickets.list.columns.subject') }}</th>
                <th class="py-2 ps-3 text-start">{{ __('tickets.list.columns.status') }}</th>
                <th class="py-2 ps-3 text-start">{{ __('tickets.list.columns.category') }}</th>
                <th class="py-2 ps-3 text-start">{{ __('tickets.list.columns.assigned_to') }}</th>
                <th class="py-2 ps-3 text-start">{{ __('tickets.list.columns.created_at') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tickets as $ticket)
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
                    <td class="py-2 ps-3 text-gray-500">{{ $ticket->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-8 text-center text-gray-500">{{ __('tickets.list.empty') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $tickets->links() }}
    </div>
</div>
