<?php

use App\Modules\Tickets\Controllers\AttachmentController;
use App\Modules\Tickets\Livewire\CreateTicket;
use App\Modules\Tickets\Livewire\ShowTicket;
use App\Modules\Tickets\Livewire\TicketList;
use App\Modules\Tickets\Models\TicketAttachment;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/tickets', TicketList::class)->name('tickets.index');
    Route::get('/tickets/create', CreateTicket::class)->name('tickets.create');

    // ULID-only routing — display_number is NEVER used as a route parameter (§2.3)
    Route::get('/tickets/{ticket}', ShowTicket::class)->name('tickets.show');

    // Attachment serve — {ticketId} is a plain string; scope bypass happens in controller
    Route::get('/tickets/{ticketId}/attachments/{attachment}', [AttachmentController::class, 'show'])
        ->name('tickets.attachments.show');
});
