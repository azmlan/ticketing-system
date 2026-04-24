<?php

namespace App\Modules\CSAT\Commands;

use App\Modules\CSAT\Models\CsatRating;
use Illuminate\Console\Command;

class CsatExpireCommand extends Command
{
    protected $signature = 'csat:expire';

    protected $description = 'Mark pending CSAT ratings as expired when their expiry date has passed.';

    public function handle(): int
    {
        $count = CsatRating::pending()
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);

        $this->info("Expired {$count} CSAT rating(s).");

        return self::SUCCESS;
    }
}
