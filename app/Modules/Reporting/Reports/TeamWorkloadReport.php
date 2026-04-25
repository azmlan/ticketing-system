<?php

namespace App\Modules\Reporting\Reports;

use App\Modules\Reporting\Contracts\ReportInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TeamWorkloadReport extends BaseReport implements ReportInterface
{
    public function headers(): array
    {
        return [
            __('reports.columns.tech_name'),
            __('reports.columns.open_count'),
        ];
    }

    public function run(array $filters): Collection
    {
        $query = DB::table('tickets')
            ->selectRaw('users.full_name as tech_name, COUNT(*) as open_count')
            ->join('users', 'tickets.assigned_to', '=', 'users.id')
            ->whereNull('tickets.deleted_at')
            ->whereNotNull('tickets.assigned_to')
            ->whereNotIn('tickets.status', ['resolved', 'closed', 'cancelled']);

        if (! empty($filters['date_from']) && ! empty($filters['date_to'])) {
            $query->whereBetween('tickets.created_at', [
                Carbon::parse($filters['date_from'])->startOfDay(),
                Carbon::parse($filters['date_to'])->endOfDay(),
            ]);
        }

        if (! empty($filters['group_id'])) {
            $query->where('tickets.group_id', $filters['group_id']);
        }

        if (! empty($filters['tech_id'])) {
            $query->where('tickets.assigned_to', $filters['tech_id']);
        }

        if (! empty($filters['priority'])) {
            $query->where('tickets.priority', $filters['priority']);
        }

        return $query
            ->groupBy('tickets.assigned_to', 'users.full_name')
            ->orderByRaw('COUNT(*) DESC')
            ->get()
            ->map(fn ($row) => [
                'tech_name' => $row->tech_name,
                'open_count' => (int) $row->open_count,
            ]);
    }
}
