<?php

use App\Http\Controllers\Activity\TripController;
use App\Http\Controllers\Activity\TripExportController;
use App\Http\Controllers\Activity\TripParticipantExportController;
use Illuminate\Support\Facades\Route;

Route::name('activity.')->prefix('activity')->group(function () {

    Route::get('trips/{trip}/participants/export', TripParticipantExportController::class)->middleware('permission:trip:manage');

    Route::get('trips/{trip}/media/{uuid}', [TripController::class, 'downloadMedia']);
    Route::get('trips/export', TripExportController::class)->middleware('permission:trip:manage');
});
