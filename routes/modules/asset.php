<?php

use App\Http\Controllers\Asset\Building\BlockController;
use App\Http\Controllers\Asset\Building\FloorController;
use App\Http\Controllers\Asset\Building\RoomController;
use Illuminate\Support\Facades\Route;

// Asset Routes
Route::prefix('asset')->name('asset.')->group(function () {
    Route::prefix('building')->name('building.')->middleware('permission:building:manage')->group(function () {
        Route::get('blocks/pre-requisite', [BlockController::class, 'preRequisite'])->name('blocks.preRequisite');
        Route::apiResource('blocks', BlockController::class);

        Route::get('floors/pre-requisite', [FloorController::class, 'preRequisite'])->name('floors.preRequisite');
        Route::apiResource('floors', FloorController::class);

        Route::get('rooms/pre-requisite', [RoomController::class, 'preRequisite'])->name('rooms.preRequisite');
        Route::apiResource('rooms', RoomController::class);
    });
});
