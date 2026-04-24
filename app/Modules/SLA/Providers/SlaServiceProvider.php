<?php

namespace App\Modules\SLA\Providers;

use App\Modules\SLA\Commands\SlaCheckCommand;
use App\Modules\SLA\Listeners\HandleTicketPriorityChanged;
use App\Modules\SLA\Listeners\HandleTicketStatusChanged;
use App\Modules\SLA\View\Components\SlaStatusBadge;
use App\Modules\Tickets\Events\TicketPriorityChanged;
use App\Modules\Tickets\Events\TicketStatusChanged;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class SlaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([SlaCheckCommand::class]);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(base_path('resources/lang'), 'sla');

        Blade::component('sla-status-badge', SlaStatusBadge::class);

        Event::listen(TicketStatusChanged::class, HandleTicketStatusChanged::class);
        Event::listen(TicketPriorityChanged::class, HandleTicketPriorityChanged::class);
    }
}
