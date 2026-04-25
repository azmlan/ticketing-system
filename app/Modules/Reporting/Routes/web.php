<?php

use App\Modules\Reporting\Livewire\ReportPage;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/reports', ReportPage::class)->name('reports.index');
});
