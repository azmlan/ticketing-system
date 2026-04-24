<?php

namespace App\Modules\SLA\Commands;

use App\Modules\SLA\Models\SlaPolicy;
use App\Modules\SLA\Models\TicketSla;
use App\Modules\SLA\Services\BusinessHoursCalculator;
use App\Modules\SLA\Services\SlaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SlaCheckCommand extends Command
{
    protected $signature = 'sla:check';

    protected $description = 'Recalculate elapsed SLA time and update statuses for all running tickets.';

    public function __construct(
        private readonly SlaService $slaService,
        private readonly BusinessHoursCalculator $calc,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $slas = TicketSla::where('is_clock_running', true)
            ->whereNotNull('last_clock_start')
            ->get();

        foreach ($slas as $sla) {
            try {
                $ticket = DB::table('tickets')
                    ->where('id', $sla->ticket_id)
                    ->whereNull('deleted_at')
                    ->first(['display_number', 'subject', 'assigned_to', 'priority']);

                if (! $ticket) {
                    continue;
                }

                $use24x7 = $ticket->priority
                    ? (bool) SlaPolicy::where('priority', $ticket->priority)->value('use_24x7')
                    : false;

                $added = $this->calc->minutesBetween($sla->last_clock_start, now(), $use24x7);

                if ($sla->response_met_at === null) {
                    $sla->response_elapsed_minutes += $added;
                }
                $sla->resolution_elapsed_minutes += $added;
                $sla->last_clock_start = now();
                $sla->save();

                $this->slaService->recalculateStatus(
                    $sla,
                    $ticket->display_number,
                    $ticket->subject,
                    $ticket->assigned_to,
                );
            } catch (\Throwable $e) {
                Log::error("sla:check failed for ticket_sla {$sla->id}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
