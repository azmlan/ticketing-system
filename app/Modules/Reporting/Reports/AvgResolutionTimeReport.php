<?php

namespace App\Modules\Reporting\Reports;

use App\Modules\Reporting\Contracts\ReportInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AvgResolutionTimeReport extends BaseReport implements ReportInterface
{
    public function headers(): array
    {
        return [
            __('reports.columns.period'),
            __('reports.columns.avg_hours'),
            __('reports.columns.count'),
        ];
    }

    public function run(array $filters): Collection
    {
        $avgExpr = DB::getDriverName() === 'sqlite'
            ? '(julianday(tickets.resolved_at) - julianday(tickets.created_at)) * 24'
            : 'TIMESTAMPDIFF(MINUTE, tickets.created_at, tickets.resolved_at) / 60.0';

        $query = DB::table('tickets')
            ->selectRaw(
                "DATE(tickets.resolved_at) as period, AVG({$avgExpr}) as avg_hours, COUNT(*) as count"
            )
            ->whereNull('tickets.deleted_at')
            ->whereNotNull('tickets.resolved_at')
            ->whereIn('tickets.status', ['resolved', 'closed']);

        // Filter on resolved_at (not created_at) for this report
        if (! empty($filters['date_from']) && ! empty($filters['date_to'])) {
            $query->whereBetween('tickets.resolved_at', [
                Carbon::parse($filters['date_from'])->startOfDay(),
                Carbon::parse($filters['date_to'])->endOfDay(),
            ]);
        }

        if (! empty($filters['category_id'])) {
            $query->where('tickets.category_id', $filters['category_id']);
        }

        if (! empty($filters['priority'])) {
            $query->where('tickets.priority', $filters['priority']);
        }

        if (! empty($filters['group_id'])) {
            $query->where('tickets.group_id', $filters['group_id']);
        }

        if (! empty($filters['tech_id'])) {
            $query->where('tickets.assigned_to', $filters['tech_id']);
        }

        return $query
            ->groupByRaw('DATE(tickets.resolved_at)')
            ->orderBy('period')
            ->get()
            ->map(fn ($row) => [
                'period' => $row->period,
                'avg_hours' => $row->avg_hours !== null ? round((float) $row->avg_hours, 1) : 0.0,
                'count' => (int) $row->count,
            ]);
    }
}
