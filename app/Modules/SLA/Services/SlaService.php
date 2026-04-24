<?php

namespace App\Modules\SLA\Services;

use App\Modules\SLA\Models\SlaPauseLog;
use App\Modules\SLA\Models\SlaPolicy;
use App\Modules\SLA\Models\TicketSla;
use App\Modules\Tickets\Events\TicketStatusChanged;

class SlaService
{
    private const PAUSED_STATUSES = [
        'on_hold', 'awaiting_approval', 'action_required', 'awaiting_final_approval',
    ];

    private const STOPPED_STATUSES = ['resolved', 'closed', 'cancelled'];

    private const RUNNING_STATUSES = ['awaiting_assignment', 'in_progress'];

    public function __construct(private BusinessHoursCalculator $calc) {}

    public function handleStatusChange(TicketStatusChanged $event): void
    {
        $toStatus   = $event->toStatus;
        $fromStatus = $event->fromStatus;
        $ticket     = $event->ticket;

        // Ticket creation — bootstrap the SLA row
        if ($fromStatus === '' && $toStatus === 'awaiting_assignment') {
            $this->bootstrap($ticket->id, $ticket->priority?->value);
            return;
        }

        $sla = TicketSla::where('ticket_id', $ticket->id)->first();
        if (! $sla) {
            return;
        }

        $use24x7 = $this->resolveUse24x7($ticket->priority?->value);

        if (in_array($toStatus, self::PAUSED_STATUSES, true)) {
            $this->pauseClock($sla, $toStatus, $use24x7);
            return;
        }

        if (in_array($toStatus, self::STOPPED_STATUSES, true)) {
            $this->stopClock($sla, $use24x7);
            return;
        }

        if (in_array($toStatus, self::RUNNING_STATUSES, true)) {
            $this->handleRunning($sla, $toStatus, $use24x7);
        }
    }

    private function bootstrap(string $ticketId, ?string $priority): void
    {
        $policy = $priority ? SlaPolicy::where('priority', $priority)->first() : null;

        TicketSla::create([
            'ticket_id'                  => $ticketId,
            'response_target_minutes'    => $policy?->response_target_minutes,
            'resolution_target_minutes'  => $policy?->resolution_target_minutes,
            'response_elapsed_minutes'   => 0,
            'resolution_elapsed_minutes' => 0,
            'response_met_at'            => null,
            'response_status'            => 'on_track',
            'resolution_status'          => 'on_track',
            'last_clock_start'           => now(),
            'is_clock_running'           => true,
        ]);
    }

    private function handleRunning(TicketSla $sla, string $toStatus, bool $use24x7): void
    {
        if (! $sla->is_clock_running) {
            $this->closePauseLog($sla);
            $sla->last_clock_start = now();
            $sla->is_clock_running = true;
        }

        // First tech assignment: flush response elapsed and seal the response timer
        if ($toStatus === 'in_progress' && $sla->response_met_at === null) {
            $added = $sla->last_clock_start
                ? $this->calc->minutesBetween($sla->last_clock_start, now(), $use24x7)
                : 0;

            $sla->response_elapsed_minutes  += $added;
            $sla->resolution_elapsed_minutes += $added;
            $sla->response_met_at            = now();
            $sla->last_clock_start           = now();
        }

        $sla->save();
    }

    private function pauseClock(TicketSla $sla, string $pauseStatus, bool $use24x7): void
    {
        if (! $sla->is_clock_running) {
            return;
        }

        $this->flushElapsed($sla, $use24x7);
        $sla->is_clock_running = false;
        $sla->save();

        SlaPauseLog::create([
            'ticket_sla_id' => $sla->id,
            'paused_at'     => now(),
            'pause_status'  => $pauseStatus,
        ]);
    }

    private function stopClock(TicketSla $sla, bool $use24x7): void
    {
        if (! $sla->is_clock_running) {
            return;
        }

        $this->flushElapsed($sla, $use24x7);
        $sla->is_clock_running = false;
        $sla->last_clock_start = null;
        $sla->save();
    }

    private function flushElapsed(TicketSla $sla, bool $use24x7): void
    {
        if (! $sla->last_clock_start) {
            return;
        }

        $added = $this->calc->minutesBetween($sla->last_clock_start, now(), $use24x7);

        if ($sla->response_met_at === null) {
            $sla->response_elapsed_minutes += $added;
        }
        $sla->resolution_elapsed_minutes += $added;
    }

    private function closePauseLog(TicketSla $sla): void
    {
        $openLog = SlaPauseLog::where('ticket_sla_id', $sla->id)
            ->whereNull('resumed_at')
            ->latest('paused_at')
            ->first();

        if ($openLog) {
            $openLog->update([
                'resumed_at'       => now(),
                'duration_minutes' => (int) $openLog->paused_at->diffInMinutes(now()),
            ]);
        }
    }

    private function resolveUse24x7(?string $priority): bool
    {
        if (! $priority) {
            return false;
        }

        return (bool) SlaPolicy::where('priority', $priority)->value('use_24x7');
    }
}
