<?php

namespace App\Modules\Reporting\Reports;

use App\Modules\Reporting\Contracts\ReportInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TicketsByStatusReport extends BaseReport implements ReportInterface
{
    public function headers(): array
    {
        return [
            __('reports.columns.status'),
            __('reports.columns.count'),
        ];
    }

    public function run(array $filters): Collection
    {
        $query = DB::table('tickets')
            ->selectRaw('tickets.status, COUNT(*) as count')
            ->whereNull('tickets.deleted_at');

        $this->applyFilters($query, $filters);

        return $query
            ->groupBy('tickets.status')
            ->orderByRaw('COUNT(*) DESC')
            ->get()
            ->map(fn ($row) => [
                'status' => __('tickets.status.' . $row->status),
                'count'  => $row->count,
            ]);
    }
}
