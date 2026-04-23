<?php

use App\Modules\Tickets\Livewire\CreateTicket;
use App\Modules\Tickets\Livewire\ShowTicket;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/tickets/create', CreateTicket::class)->name('tickets.create');

    // ULID-only routing — display_number is NEVER used as a route parameter (§2.3)
    Route::get('/tickets/{ticket}', ShowTicket::class)->name('tickets.show');
});
