<?php

namespace App\Modules\Communication\Providers;

use App\Modules\Communication\Livewire\AddComment;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class CommunicationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('communication.add-comment', AddComment::class);
    }
}
