<?php

use App\Http\Controllers\GuardianExportController;
use Illuminate\Support\Facades\Route;

Route::get('guardians/export', GuardianExportController::class)->middleware('permission:guardian:export')->name('guardians.export');
