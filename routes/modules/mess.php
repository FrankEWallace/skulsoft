<?php

use App\Http\Controllers\Mess\MealController;
use App\Http\Controllers\Mess\MealLogController;
use App\Http\Controllers\Mess\MenuItemController;
use Illuminate\Support\Facades\Route;

// Mess Routes
Route::prefix('mess')->middleware('permission:mess:config')->group(function () {});

Route::prefix('mess')->name('mess.')->group(function () {
    Route::middleware('permission:menu-item:manage')->group(function () {
        Route::get('menu-items/pre-requisite', [MenuItemController::class, 'preRequisite']);
        Route::apiResource('menu-items', MenuItemController::class);
    });

    Route::middleware('permission:meal:manage')->group(function () {
        Route::get('meals/pre-requisite', [MealController::class, 'preRequisite']);
        Route::apiResource('meals', MealController::class);
    });

    Route::get('meal-logs/pre-requisite', [MealLogController::class, 'preRequisite']);
    Route::apiResource('meal-logs', MealLogController::class);
});
