<?php

namespace App\Modules\Reporting\Reports;

use App\Modules\Reporting\Contracts\ReportInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SlaBreachesReport extends BaseReport implements ReportInterface
{
    public function headers(): array
    {
        return [
            __('reports.columns.period'),
            __('reports.columns.tech_name'),
            __('reports.columns.priority'),
            __('reports.columns.target_hours'),
            __('reports.columns.actual_hours'),
        ];
    }

    public function run(array $filters): Collection
    {
        $query = DB::table('tickets')
            ->join('ticket_sla', 'ticket_sla.ticket_id', '=', 'tickets.id')
            ->leftJoin('users', 'users.id', '=', 'tickets.assigned_to')
            ->selectRaw("
                DATE(tickets.created_at) as period,
                users.full_name as tech_name,
                tickets.priority,
                ticket_sla.resolution_target_minutes as target_minutes,
                ticket_sla.resolution_elapsed_minutes as actual_minutes
            ")
            ->whereNull('tickets.deleted_at')
            ->where('ticket_sla.resolution_status', 'breached');

        $this->applyFilters($query, $filters);

        $none = __('reports.labels.none');

        return $query
            ->orderByRaw('DATE(tickets.created_at) DESC')
            ->get()
            ->map(fn ($row) => [
                'period'       => $row->period,
                'tech_name'    => $row->tech_name ?? $none,
                'priority'     => $row->priority ? __('tickets.priority.' . $row->priority) : $none,
                'target_hours' => $row->target_minutes !== null
                    ? round($row->target_minutes / 60.0, 1)
                    : $none,
                'actual_hours' => round($row->actual_minutes / 60.0, 1),
            ]);
    }
}
