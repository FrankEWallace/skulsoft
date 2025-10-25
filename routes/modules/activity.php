<?php

use App\Http\Controllers\Activity\TripActionController;
use App\Http\Controllers\Activity\TripController;
use App\Http\Controllers\Activity\TripParticipantController;
use Illuminate\Support\Facades\Route;

// Activity Routes
Route::name('activity.')->prefix('activity')->group(function () {
    Route::get('trips/pre-requisite', [TripController::class, 'preRequisite']);

    Route::post('trips/{trip}/assets/{type}', [TripActionController::class, 'uploadAsset'])->name('trips.uploadAsset')->whereIn('type', ['cover']);
    Route::delete('trips/{trip}/assets/{type}', [TripActionController::class, 'removeAsset'])->name('trips.removeAsset')->whereIn('type', ['cover']);

    Route::post('trips/{trip}/media', [TripActionController::class, 'uploadMedia'])->name('trips.uploadMedia');
    Route::delete('trips/{trip}/media/{uuid}', [TripActionController::class, 'removeMedia'])->name('trips.removeMedia');

    Route::apiResource('trips.participants', TripParticipantController::class);

    Route::apiResource('trips', TripController::class);
});
