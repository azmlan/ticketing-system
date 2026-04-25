<?php

namespace App\Modules\Reporting\Reports;

use App\Modules\Reporting\Contracts\ReportInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CsatOverviewReport extends BaseReport implements ReportInterface
{
    public function headers(): array
    {
        return [
            __('reports.columns.period'),
            __('reports.columns.avg_rating'),
            __('reports.columns.submitted_count'),
            __('reports.columns.total_count'),
            __('reports.columns.response_rate'),
            __('reports.columns.rating_1'),
            __('reports.columns.rating_2'),
            __('reports.columns.rating_3'),
            __('reports.columns.rating_4'),
            __('reports.columns.rating_5'),
        ];
    }

    public function run(array $filters): Collection
    {
        $query = DB::table('csat_ratings')
            ->join('tickets', 'tickets.id', '=', 'csat_ratings.ticket_id')
            ->selectRaw("
                DATE(csat_ratings.created_at) as period,
                COUNT(*) as total_count,
                SUM(CASE WHEN csat_ratings.status = 'submitted' THEN 1 ELSE 0 END) as submitted_count,
                AVG(CASE WHEN csat_ratings.status = 'submitted' THEN csat_ratings.rating ELSE NULL END) as avg_rating,
                SUM(CASE WHEN csat_ratings.status = 'submitted' AND csat_ratings.rating = 1 THEN 1 ELSE 0 END) as rating_1,
                SUM(CASE WHEN csat_ratings.status = 'submitted' AND csat_ratings.rating = 2 THEN 1 ELSE 0 END) as rating_2,
                SUM(CASE WHEN csat_ratings.status = 'submitted' AND csat_ratings.rating = 3 THEN 1 ELSE 0 END) as rating_3,
                SUM(CASE WHEN csat_ratings.status = 'submitted' AND csat_ratings.rating = 4 THEN 1 ELSE 0 END) as rating_4,
                SUM(CASE WHEN csat_ratings.status = 'submitted' AND csat_ratings.rating = 5 THEN 1 ELSE 0 END) as rating_5
            ")
            ->whereNull('tickets.deleted_at');

        if (! empty($filters['date_from']) && ! empty($filters['date_to'])) {
            $query->whereBetween('csat_ratings.created_at', [
                Carbon::parse($filters['date_from'])->startOfDay(),
                Carbon::parse($filters['date_to'])->endOfDay(),
            ]);
        }

        if (! empty($filters['tech_id'])) {
            $query->where('csat_ratings.tech_id', $filters['tech_id']);
        }

        if (! empty($filters['group_id'])) {
            $query->where('tickets.group_id', $filters['group_id']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('tickets.category_id', $filters['category_id']);
        }

        if (! empty($filters['priority'])) {
            $query->where('tickets.priority', $filters['priority']);
        }

        $none = __('reports.labels.none');

        return $query
            ->groupByRaw('DATE(csat_ratings.created_at)')
            ->orderBy('period')
            ->get()
            ->map(fn ($row) => [
                'period'          => $row->period,
                'avg_rating'      => $row->avg_rating !== null
                    ? round((float) $row->avg_rating, 1)
                    : $none,
                'submitted_count' => (int) $row->submitted_count,
                'total_count'     => (int) $row->total_count,
                'response_rate'   => (int) $row->total_count > 0
                    ? number_format((int) $row->submitted_count * 100.0 / (int) $row->total_count, 1) . '%'
                    : '0.0%',
                'rating_1'        => (int) $row->rating_1,
                'rating_2'        => (int) $row->rating_2,
                'rating_3'        => (int) $row->rating_3,
                'rating_4'        => (int) $row->rating_4,
                'rating_5'        => (int) $row->rating_5,
            ]);
    }
}
