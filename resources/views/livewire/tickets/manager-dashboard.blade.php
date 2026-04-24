<div>
    {{-- ── Page Title ───────────────────────────────────────────────────────── --}}
    <h1 class="text-2xl font-bold mb-6">{{ __('tickets.dashboard.manager.title') }}</h1>

    {{-- ── Summary Stats ────────────────────────────────────────────────────── --}}
    <section class="mb-8">
        <h2 class="text-lg font-semibold mb-3">{{ __('tickets.dashboard.manager.summary_title') }}</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
            <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3">
                <div class="text-xs font-medium text-blue-600">{{ __('tickets.dashboard.manager.created_week') }}</div>
                <div class="text-2xl font-bold text-blue-800">{{ $createdWeek }}</div>
            </div>
            <div class="bg-teal-50 border border-teal-200 rounded-lg px-4 py-3">
                <div class="text-xs font-medium text-teal-600">{{ __('tickets.dashboard.manager.created_month') }}</div>
                <div class="text-2xl font-bold text-teal-800">{{ $createdMonth }}</div>
            </div>
            <div class="bg-purple-50 border border-purple-200 rounded-lg px-4 py-3">
                <div class="text-xs font-medium text-purple-600">{{ __('tickets.dashboard.manager.avg_resolution') }}</div>
                <div class="text-2xl font-bold text-purple-800">
                    {{ $avgResolutionHrs }} {{ __('tickets.dashboard.manager.avg_resolution_unit') }}
                </div>
            </div>
            <div class="bg-orange-50 border border-orange-200 rounded-lg px-4 py-3">
                <div class="text-xs font-medium text-orange-600">{{ __('tickets.dashboard.manager.unassigned_count_label') }}</div>
                <div class="text-2xl font-bold text-orange-800">{{ $unassignedCount }}</div>
            </div>
        </div>
    </section>

    {{-- ── Two-column: Status Counts + Category Counts ─────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

        {{-- Status counts --}}
        <section>
            <h2 class="text-base font-semibold mb-2">{{ __('tickets.dashboard.manager.status_counts_title') }}</h2>
            <table class="w-full text-sm border-collapse">
                <tbody>
                    @foreach ($statusCounts as $status => $count)
                        <tr class="border-b">
                            <td class="py-1.5 ps-2 text-gray-600">{{ __('tickets.status.' . $status) }}</td>
                            <td class="py-1.5 pe-2 text-end font-semibold text-gray-800">{{ $count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        {{-- Category counts --}}
        <section>
            <h2 class="text-base font-semibold mb-2">{{ __('tickets.dashboard.manager.category_counts_title') }}</h2>
            @if ($categoryCounts->isEmpty())
                <p class="text-sm text-gray-500">—</p>
            @else
                <table class="w-full text-sm border-collapse">
                    <tbody>
                        @foreach ($categoryCounts as $row)
                            <tr class="border-b">
                                <td class="py-1.5 ps-2 text-gray-600">
                                    {{ app()->getLocale() === 'ar' ? $row->name_ar : $row->name_en }}
                                </td>
                                <td class="py-1.5 pe-2 text-end font-semibold text-gray-800">{{ $row->total }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </div>

    {{-- ── SLA Overview ─────────────────────────────────────────────────────── --}}
    <section class="mb-8">
        <h2 class="text-lg font-semibold mb-3">{{ __('tickets.dashboard.manager.sla_title') }}</h2>
        <div class="flex flex-wrap gap-4 mb-4">
            <div class="bg-green-50 border border-green-200 rounded-lg px-4 py-3">
                <div class="text-xs font-medium text-green-600">{{ __('tickets.dashboard.manager.sla_compliance_rate') }}</div>
                <div class="text-2xl font-bold text-green-800">{{ $slaCompliance }}%</div>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-3">
                <div class="text-xs font-medium text-red-600">{{ __('tickets.dashboard.manager.sla_breached_count') }}</div>
                <div class="text-2xl font-bold text-red-800">{{ $breachedCount }}</div>
            </div>
        </div>

        {{-- Breached tickets table --}}
        @if ($breachedTickets->isNotEmpty())
            <h3 class="text-sm font-semibold mb-2">{{ __('tickets.dashboard.manager.sla_breached_list_title') }}</h3>
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="border-b bg-gray-50">
                        <th class="py-2 ps-3 text-start font-medium text-gray-600">{{ __('tickets.dashboard.manager.col_ticket') }}</th>
                        <th class="py-2 ps-3 text-start font-medium text-gray-600">{{ __('tickets.dashboard.manager.col_subject') }}</th>
                        <th class="py-2 ps-3 text-start font-medium text-gray-600">{{ __('tickets.dashboard.manager.col_tech') }}</th>
                        <th class="py-2 ps-3 text-end font-medium text-gray-600 pe-3">{{ __('tickets.dashboard.manager.col_overdue') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($breachedTickets as $row)
                        <tr class="border-b hover:bg-red-50">
                            <td class="py-2 ps-3 font-mono text-xs text-gray-500">{{ $row->display_number }}</td>
                            <td class="py-2 ps-3">
                                <a href="{{ route('tickets.show', $row->id) }}" class="text-blue-600 hover:underline">
                                    {{ $row->subject }}
                                </a>
                            </td>
                            <td class="py-2 ps-3 text-gray-600">
                                {{ $row->tech_name ?? __('tickets.dashboard.manager.no_tech') }}
                            </td>
                            <td class="py-2 pe-3 text-end text-red-700 font-semibold">
                                {{ $row->overdue_hours }} {{ __('tickets.dashboard.manager.overdue_unit') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-3">{{ $breachedTickets->links() }}</div>
        @endif
    </section>

    {{-- ── Escalation Queue ─────────────────────────────────────────────────── --}}
    <section class="mb-8">
        <h2 class="text-lg font-semibold mb-3">{{ __('tickets.dashboard.manager.escalation_title') }}</h2>
        @if ($escalationQueue->isEmpty())
            <p class="text-sm text-gray-500">{{ __('tickets.dashboard.manager.no_escalation') }}</p>
        @else
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="border-b bg-gray-50">
                        <th class="py-2 ps-3 text-start font-medium text-gray-600">{{ __('tickets.dashboard.manager.col_ticket') }}</th>
                        <th class="py-2 ps-3 text-start font-medium text-gray-600">{{ __('tickets.dashboard.manager.col_subject') }}</th>
                        <th class="py-2 ps-3 text-start font-medium text-gray-600">{{ __('tickets.dashboard.manager.col_status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($escalationQueue as $row)
                        <tr class="border-b hover:bg-yellow-50">
                            <td class="py-2 ps-3 font-mono text-xs text-gray-500">{{ $row->display_number }}</td>
                            <td class="py-2 ps-3">
                                <a href="{{ route('tickets.show', $row->id) }}" class="text-blue-600 hover:underline">
                                    {{ $row->subject }}
                                </a>
                            </td>
                            <td class="py-2 ps-3">{{ __('tickets.status.' . $row->status) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    {{-- ── Team Workload ────────────────────────────────────────────────────── --}}
    <section class="mb-8">
        <h2 class="text-lg font-semibold mb-3">{{ __('tickets.dashboard.manager.workload_title') }}</h2>
        @if ($teamWorkload->isEmpty())
            <p class="text-sm text-gray-500">{{ __('tickets.dashboard.manager.no_workload') }}</p>
        @else
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="border-b bg-gray-50">
                        <th class="py-2 ps-3 text-start font-medium text-gray-600">{{ __('tickets.dashboard.manager.col_tech') }}</th>
                        <th class="py-2 pe-3 text-end font-medium text-gray-600">{{ __('tickets.dashboard.manager.col_count') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($teamWorkload as $row)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-2 ps-3 text-gray-700">{{ $row->full_name }}</td>
                            <td class="py-2 pe-3 text-end font-semibold text-gray-800">{{ $row->open_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-3">{{ $teamWorkload->links() }}</div>
        @endif
    </section>

    {{-- ── Recent Activity ──────────────────────────────────────────────────── --}}
    <section>
        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
            <h2 class="text-lg font-semibold">{{ __('tickets.dashboard.manager.activity_title') }}</h2>
            <div class="flex items-center gap-2">
                <label class="text-xs font-medium text-gray-600 whitespace-nowrap">{{ __('tickets.dashboard.manager.sort_by') }}</label>
                <select wire:model.live="sortBy"
                    class="border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400">
                    <option value="updated_at">{{ __('tickets.dashboard.manager.sort_updated_at') }}</option>
                    <option value="created_at">{{ __('tickets.dashboard.manager.sort_created_at') }}</option>
                    <option value="priority">{{ __('tickets.dashboard.manager.sort_priority') }}</option>
                </select>
                <label class="text-xs font-medium text-gray-600 whitespace-nowrap">{{ __('tickets.dashboard.manager.sort_dir') }}</label>
                <select wire:model.live="sortDir"
                    class="border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400">
                    <option value="desc">{{ __('tickets.dashboard.manager.sort_desc') }}</option>
                    <option value="asc">{{ __('tickets.dashboard.manager.sort_asc') }}</option>
                </select>
            </div>
        </div>
        @if ($recentActivity->isEmpty())
            <p class="text-sm text-gray-500">{{ __('tickets.dashboard.manager.no_activity') }}</p>
        @else
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="border-b bg-gray-50">
                        <th class="py-2 ps-3 text-start font-medium text-gray-600">{{ __('tickets.dashboard.manager.col_ticket') }}</th>
                        <th class="py-2 ps-3 text-start font-medium text-gray-600">{{ __('tickets.dashboard.manager.col_subject') }}</th>
                        <th class="py-2 ps-3 text-start font-medium text-gray-600">{{ __('tickets.dashboard.manager.col_status') }}</th>
                        <th class="py-2 ps-3 text-start font-medium text-gray-600">{{ __('tickets.dashboard.manager.col_requester') }}</th>
                        <th class="py-2 ps-3 text-start font-medium text-gray-600">{{ __('tickets.dashboard.manager.col_updated') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentActivity as $row)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-2 ps-3 font-mono text-xs text-gray-500">{{ $row->display_number }}</td>
                            <td class="py-2 ps-3">
                                <a href="{{ route('tickets.show', $row->id) }}" class="text-blue-600 hover:underline">
                                    {{ $row->subject }}
                                </a>
                            </td>
                            <td class="py-2 ps-3">{{ __('tickets.status.' . $row->status) }}</td>
                            <td class="py-2 ps-3 text-gray-600">{{ $row->requester_name ?? '—' }}</td>
                            <td class="py-2 ps-3 text-gray-500">{{ $row->updated_at }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-3">{{ $recentActivity->links() }}</div>
        @endif
    </section>
</div>
