<?php

use App\Modules\Escalation\Controllers\ConditionReportAttachmentController;
use App\Modules\Escalation\Controllers\MaintenanceRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    // {conditionReportId} is a plain string; scope bypass and auth happen in the controller
    Route::get(
        '/escalation/condition-reports/{conditionReportId}/attachments/{attachment}',
        [ConditionReportAttachmentController::class, 'show']
    )->name('escalation.condition-report-attachments.show');

    // Maintenance request document download — locale ∈ {ar, en}, ULID-only ticket id
    Route::get(
        '/escalation/tickets/{ticketId}/maintenance-request/download/{locale}',
        [MaintenanceRequestController::class, 'download']
    )->name('escalation.maintenance-request.download');
});
