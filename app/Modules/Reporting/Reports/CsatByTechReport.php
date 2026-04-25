<?php

namespace App\Modules\Reporting\Reports;

use App\Modules\Reporting\Contracts\ReportInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CsatByTechReport extends BaseReport implements ReportInterface
{
    public function headers(): array
    {
        return [
            __('reports.columns.tech_name'),
            __('reports.columns.rating_count'),
            __('reports.columns.avg_rating'),
            __('reports.columns.lowest_rating'),
        ];
    }

    public function run(array $filters): Collection
    {
        $query = DB::table('csat_ratings')
            ->join('users', 'users.id', '=', 'csat_ratings.tech_id')
            ->join('tickets', 'tickets.id', '=', 'csat_ratings.ticket_id')
            ->selectRaw("
                users.full_name as tech_name,
                COUNT(*) as rating_count,
                AVG(csat_ratings.rating) as avg_rating,
                MIN(csat_ratings.rating) as lowest_rating
            ")
            ->whereNull('tickets.deleted_at')
            ->where('csat_ratings.status', 'submitted');

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

        $none = __('reports.labels.none');

        return $query
            ->groupBy('csat_ratings.tech_id', 'users.full_name')
            ->orderByRaw('AVG(csat_ratings.rating) ASC')
            ->get()
            ->map(fn ($row) => [
                'tech_name'     => $row->tech_name,
                'rating_count'  => (int) $row->rating_count,
                'avg_rating'    => $row->avg_rating !== null
                    ? round((float) $row->avg_rating, 1)
                    : $none,
                'lowest_rating' => $row->lowest_rating !== null
                    ? (int) $row->lowest_rating
                    : $none,
            ]);
    }
}
