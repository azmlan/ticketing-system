<?php

namespace App\Modules\Tickets\Providers;

use App\Modules\Tickets\Events\TicketStatusChanged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class TicketsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Future phases register their listeners here, e.g.:
        // Event::listen(TicketStatusChanged::class, SlaListener::class);
        // Event::listen(TicketStatusChanged::class, AuditListener::class);
    }
}
