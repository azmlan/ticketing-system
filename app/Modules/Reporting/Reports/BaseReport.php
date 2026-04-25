<?php

namespace App\Modules\Reporting\Reports;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;

abstract class BaseReport
{
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['date_from']) && ! empty($filters['date_to'])) {
            $query->whereBetween('tickets.created_at', [
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

        if (! empty($filters['status'])) {
            $query->where('tickets.status', $filters['status']);
        }

        return $query;
    }
}
