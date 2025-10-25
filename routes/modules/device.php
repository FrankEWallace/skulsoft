<?php

use App\Http\Controllers\DeviceController;
use Illuminate\Support\Facades\Route;

// Device Routes
Route::apiResource('devices', DeviceController::class)->middleware('role:admin');
