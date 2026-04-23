<?php

use App\Modules\Escalation\Controllers\ConditionReportAttachmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    // {conditionReportId} is a plain string; scope bypass and auth happen in the controller
    Route::get(
        '/escalation/condition-reports/{conditionReportId}/attachments/{attachment}',
        [ConditionReportAttachmentController::class, 'show']
    )->name('escalation.condition-report-attachments.show');
});
