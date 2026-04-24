<?php

namespace App\Modules\SLA\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BusinessHoursCalculator
{
    private const DEFAULT_WORKING_DAYS  = ['sun', 'mon', 'tue', 'wed', 'thu'];
    private const DEFAULT_HOURS_START   = '08:00';
    private const DEFAULT_HOURS_END     = '16:00';

    private const DAY_MAP = [
        0 => 'sun', 1 => 'mon', 2 => 'tue',
        3 => 'wed', 4 => 'thu', 5 => 'fri', 6 => 'sat',
    ];

    public function minutesBetween(Carbon $from, Carbon $to, bool $use24x7 = false): int
    {
        if ($from->gte($to)) {
            return 0;
        }

        if ($use24x7) {
            return (int) $from->diffInMinutes($to);
        }

        ['start' => $startTime, 'end' => $endTime, 'days' => $workingDays] = $this->loadConfig();

        $total   = 0;
        $current = $from->copy()->startOfDay();
        $end     = $to->copy()->startOfDay();

        while ($current->lte($end)) {
            if ($this->isWorkingDay($current, $workingDays)) {
                $dayStart = $current->copy()->setTimeFromTimeString($startTime);
                $dayEnd   = $current->copy()->setTimeFromTimeString($endTime);

                $windowStart = $from->gt($dayStart) ? $from->copy() : $dayStart->copy();
                $windowEnd   = $to->lt($dayEnd)     ? $to->copy()   : $dayEnd->copy();

                if ($windowStart->lt($windowEnd)) {
                    $total += (int) $windowStart->diffInMinutes($windowEnd);
                }
            }

            $current->addDay();
        }

        return $total;
    }

    private function isWorkingDay(Carbon $day, array $workingDays): bool
    {
        return in_array(self::DAY_MAP[$day->dayOfWeek], $workingDays, true);
    }

    private function loadConfig(): array
    {
        $start = self::DEFAULT_HOURS_START;
        $end   = self::DEFAULT_HOURS_END;
        $days  = self::DEFAULT_WORKING_DAYS;

        try {
            $settings = DB::table('app_settings')
                ->whereIn('key', ['business_hours_start', 'business_hours_end', 'working_days'])
                ->pluck('value', 'key');

            if ($settings->has('business_hours_start')) {
                $start = $settings->get('business_hours_start');
            }
            if ($settings->has('business_hours_end')) {
                $end = $settings->get('business_hours_end');
            }
            if ($settings->has('working_days')) {
                $decoded = json_decode($settings->get('working_days'), true);
                if (is_array($decoded)) {
                    $days = $decoded;
                }
            }
        } catch (\Exception) {
            // app_settings table not yet created (Phase 8); use defaults
        }

        return ['start' => $start, 'end' => $end, 'days' => $days];
    }
}
