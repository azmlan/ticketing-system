<?php

namespace App\Modules\Reporting\Reports;

use App\Modules\Reporting\Contracts\ReportInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SlaComplianceReport extends BaseReport implements ReportInterface
{
    public function headers(): array
    {
        return [
            __('reports.columns.priority'),
            __('reports.columns.total_count'),
            __('reports.columns.within_sla_count'),
            __('reports.columns.compliance_pct'),
        ];
    }

    public function run(array $filters): Collection
    {
        $query = DB::table('tickets')
            ->join('ticket_sla', 'ticket_sla.ticket_id', '=', 'tickets.id')
            ->selectRaw("
                tickets.priority,
                COUNT(ticket_sla.id) as total_count,
                SUM(CASE WHEN ticket_sla.resolution_status != 'breached' THEN 1 ELSE 0 END) as within_sla_count,
                SUM(CASE WHEN ticket_sla.resolution_status != 'breached' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(ticket_sla.id), 0) as compliance_pct
            ")
            ->whereNull('tickets.deleted_at');

        $this->applyFilters($query, $filters);

        $priorityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
        $none = __('reports.labels.none');

        return $query
            ->groupBy('tickets.priority')
            ->get()
            ->sortBy(fn ($row) => $priorityOrder[$row->priority] ?? 99)
            ->values()
            ->map(fn ($row) => [
                'priority'         => $row->priority ? __('tickets.priority.' . $row->priority) : $none,
                'total_count'      => (int) $row->total_count,
                'within_sla_count' => (int) $row->within_sla_count,
                'compliance_pct'   => $row->compliance_pct !== null
                    ? number_format((float) $row->compliance_pct, 1) . '%'
                    : $none,
            ]);
    }
}
