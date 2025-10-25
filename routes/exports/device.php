<?php

use App\Http\Controllers\DeviceExportController;
use Illuminate\Support\Facades\Route;

Route::get('devices/export', DeviceExportController::class)->middleware('role:admin');
