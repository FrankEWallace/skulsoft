<?php

use App\Http\Controllers\Integration\DeviceTimesheetController;
use App\Http\Controllers\Integration\TallyTransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/tally/transactions', TallyTransactionController::class)->name('tally.transactions');

Route::post('/attendance/timesheet', [DeviceTimesheetController::class, 'store'])
    ->name('device.timesheet.store')
    ->middleware('throttle:biometric');

Route::post('/attendance/import', [DeviceTimesheetController::class, 'import'])
    ->name('device.timesheet.import')
    ->middleware('throttle:biometric');
