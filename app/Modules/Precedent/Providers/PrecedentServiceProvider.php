<?php

namespace App\Modules\Precedent\Providers;

use Illuminate\Support\ServiceProvider;

class PrecedentServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadTranslationsFrom(base_path('resources/lang'), 'precedent');
    }
}
