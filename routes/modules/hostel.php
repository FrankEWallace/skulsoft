<?php

use App\Http\Controllers\Hostel\BlockController;
use App\Http\Controllers\Hostel\BlockInchargeController;
use App\Http\Controllers\Hostel\FloorController;
use App\Http\Controllers\Hostel\RoomAllocationController;
use App\Http\Controllers\Hostel\RoomController;
use Illuminate\Support\Facades\Route;

// Asset Routes
Route::prefix('hostel')->name('hostel.')->middleware('permission:hostel:manage')->group(function () {
    Route::get('blocks/pre-requisite', [BlockController::class, 'preRequisite'])->name('blocks.preRequisite');
    Route::apiResource('blocks', BlockController::class);

    Route::get('block-incharges/pre-requisite', [BlockInchargeController::class, 'preRequisite'])->name('block-incharges.preRequisite');

    Route::apiResource('block-incharges', BlockInchargeController::class);

    Route::get('floors/pre-requisite', [FloorController::class, 'preRequisite'])->name('floors.preRequisite');
    Route::apiResource('floors', FloorController::class);

    Route::get('rooms/pre-requisite', [RoomController::class, 'preRequisite'])->name('rooms.preRequisite');
    Route::apiResource('rooms', RoomController::class);

    Route::get('room-allocations/pre-requisite', [RoomAllocationController::class, 'preRequisite'])->name('room-allocations.preRequisite');
    Route::apiResource('room-allocations', RoomAllocationController::class);
});
