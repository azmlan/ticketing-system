<?php

use App\Modules\Reporting\Controllers\ExportController;
use App\Modules\Reporting\Livewire\ReportPage;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/reports', ReportPage::class)->name('reports.index');
    Route::get('/reports/export', [ExportController::class, 'export'])->name('reports.export');
    Route::get('/reports/exports/{export}/download', [ExportController::class, 'download'])->name('reports.exports.download');
});
