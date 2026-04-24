<?php

namespace App\Modules\Tickets\Providers;

use App\Modules\Shared\Contracts\SearchServiceInterface;
use App\Modules\Tickets\Livewire\CreateTicket;
use App\Modules\Tickets\Livewire\EmployeeDashboard;
use App\Modules\Tickets\Livewire\ShowTicket;
use App\Modules\Tickets\Livewire\TicketList;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Models\TicketAttachment;
use App\Modules\Tickets\Policies\TicketAttachmentPolicy;
use App\Modules\Tickets\Policies\TicketPolicy;
use App\Modules\Tickets\Search\MySqlSearchDriver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class TicketsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SearchServiceInterface::class, MySqlSearchDriver::class);
    }

    public function boot(): void
    {
        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(TicketAttachment::class, TicketAttachmentPolicy::class);

        Livewire::component('tickets.create-ticket', CreateTicket::class);
        Livewire::component('tickets.employee-dashboard', EmployeeDashboard::class);
        Livewire::component('tickets.show-ticket', ShowTicket::class);
        Livewire::component('tickets.ticket-list', TicketList::class);

        Route::middleware('web')->group(__DIR__.'/../Routes/web.php');

        // Future phases register TicketStatusChanged listeners here, e.g.:
        // Event::listen(TicketStatusChanged::class, SlaListener::class);
        // Event::listen(TicketStatusChanged::class, AuditListener::class);
    }
}
