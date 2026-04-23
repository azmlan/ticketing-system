<?php

namespace App\Modules\Escalation\Providers;

use App\Modules\Escalation\Listeners\GenerateMaintenanceRequestOnActionRequired;
use App\Modules\Escalation\Livewire\ReviewConditionReport;
use App\Modules\Escalation\Livewire\SubmitConditionReport;
use App\Modules\Tickets\Events\TicketStatusChanged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class EscalationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('escalation.submit-condition-report', SubmitConditionReport::class);
        Livewire::component('escalation.review-condition-report', ReviewConditionReport::class);

        Route::middleware('web')->group(__DIR__.'/../Routes/web.php');

        Event::listen(TicketStatusChanged::class, GenerateMaintenanceRequestOnActionRequired::class);
    }
}
