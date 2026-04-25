<div>
    {{-- Filter bar --}}
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            {{-- Report type --}}
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('reports.filters.report_type') }}</label>
                <select wire:model.live="reportType" class="w-full border rounded px-3 py-2 text-sm">
                    @foreach ($reportTypes as $type)
                        <option value="{{ $type }}">{{ __('reports.types.' . $type) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Date from --}}
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('reports.filters.date_from') }}</label>
                <input type="date" wire:model.live="dateFrom"
                       class="w-full border rounded px-3 py-2 text-sm" />
            </div>

            {{-- Date to --}}
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('reports.filters.date_to') }}</label>
                <input type="date" wire:model.live="dateTo"
                       class="w-full border rounded px-3 py-2 text-sm" />
            </div>

            {{-- Status --}}
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('reports.filters.status') }}</label>
                <select wire:model.live="status" class="w-full border rounded px-3 py-2 text-sm">
                    <option value="">{{ __('reports.filters.all') }}</option>
                    @foreach (\App\Modules\Tickets\Enums\TicketStatus::cases() as $case)
                        <option value="{{ $case->value }}">{{ __('tickets.status.' . $case->value) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Category --}}
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('reports.filters.category') }}</label>
                <select wire:model.live="categoryId" class="w-full border rounded px-3 py-2 text-sm">
                    <option value="">{{ __('reports.filters.all') }}</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Priority --}}
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('reports.filters.priority') }}</label>
                <select wire:model.live="priority" class="w-full border rounded px-3 py-2 text-sm">
                    <option value="">{{ __('reports.filters.all') }}</option>
                    @foreach (\App\Modules\Tickets\Enums\TicketPriority::cases() as $case)
                        <option value="{{ $case->value }}">{{ __('tickets.priority.' . $case->value) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Group --}}
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('reports.filters.group') }}</label>
                <select wire:model.live="groupId" class="w-full border rounded px-3 py-2 text-sm">
                    <option value="">{{ __('reports.filters.all') }}</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Tech --}}
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('reports.filters.tech') }}</label>
                <select wire:model.live="techId" class="w-full border rounded px-3 py-2 text-sm">
                    <option value="">{{ __('reports.filters.all') }}</option>
                    @foreach ($techs as $tech)
                        <option value="{{ $tech->id }}">{{ $tech->full_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-3 flex justify-between items-center">
            <div class="flex gap-2">
                <a href="{{ $csvExportUrl }}"
                   class="text-sm bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded">
                    {{ __('reports.export.download_csv') }}
                </a>
                <a href="{{ $xlsxExportUrl }}"
                   class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded">
                    {{ __('reports.export.download_xlsx') }}
                </a>
            </div>
            <button wire:click="resetFilters"
                    class="text-sm text-gray-500 hover:text-gray-700 px-3 py-1 rounded border">
                {{ __('common.clear') }}
            </button>
        </div>
    </div>

    {{-- Report table --}}
    <div class="bg-white rounded-lg shadow">
        <div class="px-4 py-3 border-b font-semibold text-sm">
            {{ __('reports.types.' . $reportType) }}
        </div>

        @if (! $dateFrom || ! $dateTo)
            <div class="p-6 text-center text-gray-500 text-sm">
                {{ __('reports.labels.select_dates') }}
            </div>
        @elseif ($rows->isEmpty())
            <div class="p-6 text-center text-gray-500 text-sm">
                {{ __('reports.labels.no_data') }}
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-start">
                        <tr>
                            @foreach ($headers as $header)
                                <th class="px-4 py-3 font-medium text-gray-600 text-start">{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($rows as $row)
                            <tr class="hover:bg-gray-50">
                                @foreach (array_values((array) $row) as $cell)
                                    <td class="px-4 py-3 text-gray-800">{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
