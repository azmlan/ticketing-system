<?php

namespace App\Modules\SLA\Providers;

use Illuminate\Support\ServiceProvider;

class SlaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(
            base_path('resources/lang'),
            'sla'
        );
    }
}
