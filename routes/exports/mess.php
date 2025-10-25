<?php

use App\Http\Controllers\Mess\MealExportController;
use App\Http\Controllers\Mess\MealLogExportController;
use App\Http\Controllers\Mess\MenuItemExportController;
use Illuminate\Support\Facades\Route;

Route::get('mess/menu-items/export', MenuItemExportController::class)->middleware('permission:menu-item:manage');

Route::get('mess/meals/export', MealExportController::class)->middleware('permission:meal:manage');

Route::get('mess/meal-logs/export', MealLogExportController::class)->middleware('permission:meal-log:export');
