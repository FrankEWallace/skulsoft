<?php

use App\Http\Controllers\Calendar\CelebrationExportController;
use App\Http\Controllers\Calendar\EventController;
use App\Http\Controllers\Calendar\EventExportController;
use App\Http\Controllers\Calendar\HolidayExportController;
use Illuminate\Support\Facades\Route;

Route::name('calendar.')->prefix('calendar')->group(function () {
    Route::get('holidays/export', HolidayExportController::class)->middleware('permission:holiday:export')->name('holidays.export');

    Route::get('celebrations/export', CelebrationExportController::class)->middleware('permission:celebration:export');

    Route::get('events/{event}/media/{uuid}', [EventController::class, 'downloadMedia']);
    Route::get('events/export', EventExportController::class)->middleware('permission:event:export');
});
