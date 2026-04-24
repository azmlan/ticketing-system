<?php

namespace App\Modules\CSAT\Providers;

use App\Modules\CSAT\Commands\CsatExpireCommand;
use App\Modules\CSAT\Listeners\HandleCsatOnResolution;
use App\Modules\Tickets\Events\TicketStatusChanged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class CsatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([CsatExpireCommand::class]);
    }

    public function boot(): void
    {
        Event::listen(TicketStatusChanged::class, HandleCsatOnResolution::class);
    }
}
