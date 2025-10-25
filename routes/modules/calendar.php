<?php

use App\Http\Controllers\Calendar\CelebrationController;
use App\Http\Controllers\Calendar\EventActionController;
use App\Http\Controllers\Calendar\EventController;
use App\Http\Controllers\Calendar\HolidayController;
use Illuminate\Support\Facades\Route;

// Calendar Routes
Route::name('calendar.')->prefix('calendar')->group(function () {
    Route::get('holidays/pre-requisite', [HolidayController::class, 'preRequisite'])->name('holidays.preRequisite');
    Route::apiResource('holidays', HolidayController::class);

    Route::get('celebrations/pre-requisite', [CelebrationController::class, 'preRequisite']);
    Route::apiResource('celebrations', CelebrationController::class)->only(['index'])->middleware('permission:celebration:read');

    Route::get('events/pre-requisite', [EventController::class, 'preRequisite']);

    Route::post('events/{event}/assets/{type}', [EventActionController::class, 'uploadAsset'])->name('events.uploadAsset')->whereIn('type', ['cover']);
    Route::delete('events/{event}/assets/{type}', [EventActionController::class, 'removeAsset'])->name('events.removeAsset')->whereIn('type', ['cover']);

    Route::post('events/{event}/pin', [EventActionController::class, 'pin'])->name('events.pin');
    Route::post('events/{event}/unpin', [EventActionController::class, 'unpin'])->name('events.unpin');

    Route::apiResource('events', EventController::class);
});
