<?php

use App\Http\Controllers\Asset\Building\BlockExportController;
use App\Http\Controllers\Asset\Building\FloorExportController;
use App\Http\Controllers\Asset\Building\RoomExportController;
use Illuminate\Support\Facades\Route;

Route::prefix('asset')->name('asset.')->group(function () {
    Route::prefix('building')->name('building.')->middleware('permission:building:manage')->group(function () {
        Route::get('blocks/export', BlockExportController::class);
        Route::get('floors/export', FloorExportController::class);
        Route::get('rooms/export', RoomExportController::class);
    });
});
