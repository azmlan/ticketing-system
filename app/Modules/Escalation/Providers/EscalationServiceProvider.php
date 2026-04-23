<?php

namespace App\Modules\Escalation\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class EscalationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/../Routes/web.php');
    }
}
