<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ScreenLockController;
use App\Http\Controllers\Auth\TwoFactorSecurity;
use App\Http\Controllers\Config\ConfigController;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::middleware(['guest'])->group(function () {
    Route::post('login', [LoginController::class, 'login'])
        ->middleware('throttle:auth')
        ->name('login');

    Route::post('login/otp/request', [LoginController::class, 'otpRequest'])
        ->middleware('throttle:otp')
        ->name('login.otp.request');

    Route::post('login/otp/confirm', [LoginController::class, 'otpConfirm'])
        ->middleware('throttle:auth')
        ->name('login.otp.confirm');

    Route::post('password/request', [PasswordController::class, 'password'])
        ->middleware('throttle:auth')
        ->name('password.request');

    Route::post('password/confirm', [PasswordController::class, 'confirm'])
        ->name('password.confirm');

    Route::post('password/reset', [PasswordController::class, 'reset'])
        ->middleware('test.mode.restriction')
        ->name('password.reset');

    Route::post('register', [RegisterController::class, 'register'])
        ->middleware('throttle:auth')
        ->name('register');

    Route::post('register/email', [RegisterController::class, 'emailRequest'])
        ->middleware('throttle:auth')
        ->name('register.email.request');

    Route::post('register/verify', [RegisterController::class, 'verify'])
        ->name('register.verify');
});

Route::middleware(['auth:sanctum', 'under.maintenance'])->group(function () {
    Route::post('logout', [LoginController::class, 'logout'])
        ->name('logout');

    Route::post('security', TwoFactorSecurity::class)
        ->middleware('throttle:auth')
        ->name('security');
});

// Auth Routes

Route::middleware(['auth:sanctum', 'two.factor.security', 'under.maintenance'])->group(function () {
    Route::post('unlock', [ScreenLockController::class, 'unlock'])
        ->name('unlock');
    Route::get('user', [AuthController::class, 'me'])
        ->name('me');
    Route::get('config', [ConfigController::class, 'index'])
        ->name('auth.config');
});

Route::middleware(['auth:sanctum', 'two.factor.security', 'screen.lock', 'under.maintenance'])->group(function () {
    Route::post('lock', [ScreenLockController::class, 'lock'])
        ->name('lock');
});
