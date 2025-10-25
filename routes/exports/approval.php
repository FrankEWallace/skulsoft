<?php

use App\Http\Controllers\Approval\Report\RequestSummaryExportController;
use App\Http\Controllers\Approval\RequestController;
use App\Http\Controllers\Approval\RequestExportController;
use App\Http\Controllers\Approval\TypeExportController;
use Illuminate\Support\Facades\Route;

Route::prefix('approval')->name('approval.')->group(function () {
    Route::get('types/export', TypeExportController::class)->middleware('permission:approval-type:manage');

    Route::get('requests/{request}/media/{uuid}', [RequestController::class, 'downloadMedia']);

    Route::get('requests/{request}/export', [RequestController::class, 'export'])->middleware('permission:approval-request:read');

    Route::get('requests/export', RequestExportController::class)->middleware('permission:approval-request:export');

    Route::get('reports/request-summary/export', RequestSummaryExportController::class)->middleware('permission:approval:report')->name('approval.reports.request-summary.export');
});
