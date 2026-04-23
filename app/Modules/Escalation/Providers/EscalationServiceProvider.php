<?php

namespace App\Modules\Escalation\Providers;

use App\Modules\Escalation\Livewire\SubmitConditionReport;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class EscalationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('escalation.submit-condition-report', SubmitConditionReport::class);

        Route::middleware('web')->group(__DIR__.'/../Routes/web.php');
    }
}
