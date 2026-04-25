<?php

namespace App\Modules\Reporting\Reports;

use App\Modules\Reporting\Contracts\ReportInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TicketsByPriorityReport extends BaseReport implements ReportInterface
{
    public function headers(): array
    {
        return [
            __('reports.columns.priority'),
            __('reports.columns.count'),
        ];
    }

    public function run(array $filters): Collection
    {
        $query = DB::table('tickets')
            ->selectRaw('tickets.priority, COUNT(*) as count')
            ->whereNull('tickets.deleted_at');

        $this->applyFilters($query, $filters);

        return $query
            ->groupBy('tickets.priority')
            ->orderByRaw('COUNT(*) DESC')
            ->get()
            ->map(fn ($row) => [
                'priority' => $row->priority ? __('tickets.priority.'.$row->priority) : __('reports.labels.none'),
                'count' => $row->count,
            ]);
    }
}
