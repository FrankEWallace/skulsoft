<?php

use App\Http\Controllers\Command\UpdateController;
use Illuminate\Support\Facades\Route;

Route::get('clear-cache', function () {
    \Artisan::call('optimize:clear');

    return view('index', ['message' => 'Cache cleared.']);
})->name('clear.cache');

Route::get('clear-log', function () {
    exec('rm -f '.storage_path('logs/*.log'));
    touch(storage_path('logs/laravel.log'));

    return view('index', ['message' => 'Log cleared.']);
})->name('clear.log');

Route::get('sync-locale/{code}', function ($code) {
    \Artisan::call('sync:locale', ['--force' => true, 'code' => $code]);

    return view('index', ['message' => 'Locale synced.']);
})->name('sync.locale');

Route::get('sync-template', function () {
    \Artisan::call('sync:template', ['--force' => true]);

    return view('index', ['message' => 'Template synced.']);
})->name('sync.template');

Route::get('sync-permission', function () {
    \Artisan::call('sync:permission', ['--force' => true]);

    return view('index', ['message' => 'Permission synced.']);
})->name('sync.permission');

Route::get('migrate', function () {
    \Artisan::call('migrate', ['--force' => true]);

    return view('index', ['message' => 'Migration complete.']);
})->name('migrate');

Route::get('update', UpdateController::class)->name('update.app');
