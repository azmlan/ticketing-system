<?php

namespace App\Modules\Reporting\Reports;

use App\Modules\Reporting\Contracts\ReportInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TicketVolumeReport extends BaseReport implements ReportInterface
{
    public function headers(): array
    {
        return [
            __('reports.columns.period'),
            __('reports.columns.count'),
        ];
    }

    public function run(array $filters): Collection
    {
        $query = DB::table('tickets')
            ->selectRaw('DATE(tickets.created_at) as period, COUNT(*) as count')
            ->whereNull('tickets.deleted_at');

        $this->applyFilters($query, $filters);

        return $query
            ->groupByRaw('DATE(tickets.created_at)')
            ->orderBy('period')
            ->get()
            ->map(fn ($row) => [
                'period' => $row->period,
                'count'  => $row->count,
            ]);
    }
}
