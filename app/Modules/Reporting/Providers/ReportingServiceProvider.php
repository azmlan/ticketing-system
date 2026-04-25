<?php

namespace App\Modules\Reporting\Providers;

use App\Modules\Reporting\Contracts\ReportServiceInterface;
use App\Modules\Reporting\Livewire\ReportPage;
use App\Modules\Reporting\Services\ReportService;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ReportingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ReportServiceInterface::class, ReportService::class);
        $this->app->singleton(ReportService::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(base_path('resources/lang'), 'reports');
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');

        Livewire::component('reports.report-page', ReportPage::class);
    }
}
