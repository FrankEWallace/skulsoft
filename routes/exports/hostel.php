<?php

use App\Http\Controllers\Hostel\BlockExportController;
use App\Http\Controllers\Hostel\BlockInchargeExportController;
use App\Http\Controllers\Hostel\FloorExportController;
use App\Http\Controllers\Hostel\RoomAllocationExportController;
use App\Http\Controllers\Hostel\RoomExportController;
use Illuminate\Support\Facades\Route;

Route::prefix('hostel')->name('hostel.')->middleware('permission:building:manage')->group(function () {
    Route::get('block-incharges/export', BlockInchargeExportController::class)->middleware('permission:hostel-incharge:export')->name('block-incharges.export');

    Route::get('blocks/export', BlockExportController::class);
    Route::get('floors/export', FloorExportController::class);
    Route::get('rooms/export', RoomExportController::class);

    Route::get('room-allocations/export', RoomAllocationExportController::class)->middleware('permission:hostel-room-allocation:export')->name('room-allocations.export');
});
