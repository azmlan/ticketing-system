<?php

namespace App\Modules\Tickets\Services;

use Illuminate\Support\Facades\DB;

class TicketCounterService
{
    public function generate(): string
    {
        return DB::transaction(function () {
            $counter = DB::table('ticket_counters')
                ->where('id', 1)
                ->lockForUpdate()
                ->first();

            $next = $counter->last_number + 1;

            DB::table('ticket_counters')
                ->where('id', 1)
                ->update(['last_number' => $next]);

            return 'TKT-' . str_pad((string) $next, 7, '0', STR_PAD_LEFT);
        });
    }
}
