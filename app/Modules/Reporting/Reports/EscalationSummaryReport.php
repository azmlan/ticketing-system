<?php

namespace App\Modules\Reporting\Reports;

use App\Modules\Reporting\Contracts\ReportInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EscalationSummaryReport extends BaseReport implements ReportInterface
{
    public function headers(): array
    {
        return [
            __('reports.columns.period'),
            __('reports.columns.triggered'),
            __('reports.columns.approved'),
            __('reports.columns.rejected'),
        ];
    }

    public function run(array $filters): Collection
    {
        $query = DB::table('condition_reports')
            ->selectRaw("
                DATE(condition_reports.created_at) as period,
                COUNT(*) as triggered,
                SUM(CASE WHEN condition_reports.status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN condition_reports.status = 'rejected' THEN 1 ELSE 0 END) as rejected
            ");

        if (! empty($filters['date_from']) && ! empty($filters['date_to'])) {
            $query->whereBetween('condition_reports.created_at', [
                Carbon::parse($filters['date_from'])->startOfDay(),
                Carbon::parse($filters['date_to'])->endOfDay(),
            ]);
        }

        if (! empty($filters['tech_id'])) {
            $query->where('condition_reports.tech_id', $filters['tech_id']);
        }

        return $query
            ->groupByRaw('DATE(condition_reports.created_at)')
            ->orderBy('period')
            ->get()
            ->map(fn ($row) => [
                'period'    => $row->period,
                'triggered' => (int) $row->triggered,
                'approved'  => (int) $row->approved,
                'rejected'  => (int) $row->rejected,
            ]);
    }
}
