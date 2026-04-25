<?php

namespace App\Modules\Reporting\Services;

use App\Modules\Reporting\Contracts\ReportInterface;
use App\Modules\Reporting\Contracts\ReportServiceInterface;
use App\Modules\Reporting\Reports\AvgResolutionTimeReport;
use App\Modules\Reporting\Reports\EscalationSummaryReport;
use App\Modules\Reporting\Reports\TechPerformanceReport;
use App\Modules\Reporting\Reports\TeamWorkloadReport;
use App\Modules\Reporting\Reports\TicketsByCategoryReport;
use App\Modules\Reporting\Reports\TicketsByPriorityReport;
use App\Modules\Reporting\Reports\TicketsByStatusReport;
use App\Modules\Reporting\Reports\TicketVolumeReport;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ReportService implements ReportServiceInterface
{
    private array $registry = [
        'ticket_volume'       => TicketVolumeReport::class,
        'tickets_by_status'   => TicketsByStatusReport::class,
        'tickets_by_category' => TicketsByCategoryReport::class,
        'tickets_by_priority' => TicketsByPriorityReport::class,
        'avg_resolution_time' => AvgResolutionTimeReport::class,
        'tech_performance'    => TechPerformanceReport::class,
        'team_workload'       => TeamWorkloadReport::class,
        'escalation_summary'  => EscalationSummaryReport::class,
    ];

    public function run(string $type, array $filters): Collection
    {
        if (! isset($this->registry[$type])) {
            throw new InvalidArgumentException("Unknown report type: {$type}");
        }

        /** @var ReportInterface $report */
        $report = app($this->registry[$type]);

        return $report->run($filters);
    }

    public function headers(string $type): array
    {
        if (! isset($this->registry[$type])) {
            return [];
        }

        /** @var ReportInterface $report */
        $report = app($this->registry[$type]);

        return $report->headers();
    }

    public function types(): array
    {
        return array_keys($this->registry);
    }
}
