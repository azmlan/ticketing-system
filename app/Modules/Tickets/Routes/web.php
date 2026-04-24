<?php

use App\Modules\Tickets\Controllers\AttachmentController;
use App\Modules\Tickets\Livewire\CreateTicket;
use App\Modules\Tickets\Livewire\EmployeeDashboard;
use App\Modules\Tickets\Livewire\ManagerDashboard;
use App\Modules\Tickets\Livewire\ShowTicket;
use App\Modules\Tickets\Livewire\TechDashboard;
use App\Modules\Tickets\Livewire\TicketList;
use App\Modules\Tickets\Models\TicketAttachment;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/tickets', TicketList::class)->name('tickets.index');
    Route::get('/dashboard', EmployeeDashboard::class)->name('tickets.dashboard.employee');
    Route::get('/tech/dashboard', TechDashboard::class)->name('tickets.dashboard.tech');
    Route::get('/manager/dashboard', ManagerDashboard::class)->name('tickets.dashboard.manager');
    Route::get('/tickets/create', CreateTicket::class)->name('tickets.create');

    // ULID-only routing — display_number is NEVER used as a route parameter (§2.3)
    Route::get('/tickets/{ticket}', ShowTicket::class)->name('tickets.show');

    // Attachment serve — {ticketId} is a plain string; scope bypass happens in controller
    Route::get('/tickets/{ticketId}/attachments/{attachment}', [AttachmentController::class, 'show'])
        ->name('tickets.attachments.show');
});
