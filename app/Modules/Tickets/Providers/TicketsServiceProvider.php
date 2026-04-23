<?php

namespace App\Modules\Tickets\Providers;

use App\Modules\Tickets\Livewire\CreateTicket;
use App\Modules\Tickets\Livewire\ShowTicket;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Illuminate\Support\Facades\Route;

class TicketsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('tickets.create-ticket', CreateTicket::class);
        Livewire::component('tickets.show-ticket', ShowTicket::class);

        Route::middleware('web')->group(__DIR__ . '/../Routes/web.php');

        // Future phases register TicketStatusChanged listeners here, e.g.:
        // Event::listen(TicketStatusChanged::class, SlaListener::class);
        // Event::listen(TicketStatusChanged::class, AuditListener::class);
    }
}
