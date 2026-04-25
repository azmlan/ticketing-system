<?php

namespace App\Modules\CSAT\Providers;

use App\Modules\CSAT\Commands\CsatExpireCommand;
use App\Modules\CSAT\Listeners\HandleCsatOnResolution;
use App\Modules\CSAT\Livewire\CsatPromptModal;
use App\Modules\CSAT\Livewire\CsatRatingSection;
use App\Modules\Tickets\Events\TicketStatusChanged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class CsatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([CsatExpireCommand::class]);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(base_path('resources/lang'), 'csat');

        Livewire::component('csat.prompt-modal', CsatPromptModal::class);
        Livewire::component('csat.rating-section', CsatRatingSection::class);

        Event::listen(TicketStatusChanged::class, HandleCsatOnResolution::class);
    }
}
