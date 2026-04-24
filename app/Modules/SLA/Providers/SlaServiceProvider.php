<?php

namespace App\Modules\SLA\Providers;

use App\Modules\SLA\Listeners\HandleTicketStatusChanged;
use App\Modules\Tickets\Events\TicketStatusChanged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class SlaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(base_path('resources/lang'), 'sla');

        Event::listen(TicketStatusChanged::class, HandleTicketStatusChanged::class);
    }
}
