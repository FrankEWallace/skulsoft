<?php

use App\Http\Controllers\Discipline\IncidentController;
use App\Http\Controllers\Discipline\IncidentExportController;
use Illuminate\Support\Facades\Route;

Route::name('discipline.')->prefix('discipline')->group(function () {

    Route::get('incidents/{incident}/media/{uuid}', [IncidentController::class, 'downloadMedia']);

    Route::get('incidents/export', IncidentExportController::class)->middleware('permission:incident:manage');
});
