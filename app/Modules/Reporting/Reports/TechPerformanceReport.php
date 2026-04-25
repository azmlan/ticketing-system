<?php

namespace App\Modules\Reporting\Reports;

use App\Modules\Reporting\Contracts\ReportInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TechPerformanceReport extends BaseReport implements ReportInterface
{
    public function headers(): array
    {
        return [
            __('reports.columns.tech_name'),
            __('reports.columns.resolved_count'),
            __('reports.columns.avg_csat'),
            __('reports.columns.sla_compliance_pct'),
        ];
    }

    public function run(array $filters): Collection
    {
        $query = DB::table('tickets')
            ->selectRaw("
                users.full_name as tech_name,
                COUNT(tickets.id) as resolved_count,
                AVG(csat_ratings.rating) as avg_csat,
                SUM(CASE WHEN ticket_sla.resolution_status IS NOT NULL AND ticket_sla.resolution_status != 'breached' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(ticket_sla.id), 0) as sla_compliance_pct
            ")
            ->join('users', 'tickets.assigned_to', '=', 'users.id')
            ->leftJoin('csat_ratings', function ($join) {
                $join->on('csat_ratings.ticket_id', '=', 'tickets.id')
                    ->where('csat_ratings.status', '=', 'submitted');
            })
            ->leftJoin('ticket_sla', 'ticket_sla.ticket_id', '=', 'tickets.id')
            ->whereNull('tickets.deleted_at')
            ->whereIn('tickets.status', ['resolved', 'closed'])
            ->whereNotNull('tickets.assigned_to');

        if (! empty($filters['date_from']) && ! empty($filters['date_to'])) {
            $query->whereBetween('tickets.resolved_at', [
                Carbon::parse($filters['date_from'])->startOfDay(),
                Carbon::parse($filters['date_to'])->endOfDay(),
            ]);
        }

        if (! empty($filters['tech_id'])) {
            $query->where('tickets.assigned_to', $filters['tech_id']);
        }

        if (! empty($filters['group_id'])) {
            $query->where('tickets.group_id', $filters['group_id']);
        }

        $none = __('reports.labels.none');

        return $query
            ->groupBy('tickets.assigned_to', 'users.full_name')
            ->orderByRaw('COUNT(tickets.id) DESC')
            ->get()
            ->map(fn ($row) => [
                'tech_name' => $row->tech_name,
                'resolved_count' => (int) $row->resolved_count,
                'avg_csat' => $row->avg_csat !== null
                    ? round((float) $row->avg_csat, 1)
                    : $none,
                'sla_compliance_pct' => $row->sla_compliance_pct !== null
                    ? number_format((float) $row->sla_compliance_pct, 1).'%'
                    : $none,
            ]);
    }
}
