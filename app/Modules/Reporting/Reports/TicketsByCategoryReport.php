<?php

namespace App\Modules\Reporting\Reports;

use App\Modules\Reporting\Contracts\ReportInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TicketsByCategoryReport extends BaseReport implements ReportInterface
{
    public function headers(): array
    {
        return [
            __('reports.columns.category'),
            __('reports.columns.count'),
        ];
    }

    public function run(array $filters): Collection
    {
        $locale = app()->getLocale();
        $nameCol = $locale === 'ar' ? 'categories.name_ar' : 'categories.name_en';

        $query = DB::table('tickets')
            ->selectRaw("COALESCE($nameCol, ?) as category, COUNT(*) as count", [__('reports.labels.uncategorised')])
            ->leftJoin('categories', 'tickets.category_id', '=', 'categories.id')
            ->whereNull('tickets.deleted_at');

        $this->applyFilters($query, $filters);

        return $query
            ->groupBy('tickets.category_id', 'categories.name_ar', 'categories.name_en')
            ->orderByRaw('COUNT(*) DESC')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category,
                'count'    => $row->count,
            ]);
    }
}
