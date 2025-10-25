<?php

use App\Http\Controllers\Guardian\PhotoController;
use App\Http\Controllers\Guardian\UserController;
use App\Http\Controllers\GuardianController;
use Illuminate\Support\Facades\Route;

Route::post('guardians/{guardian}/user/confirm', [UserController::class, 'confirm'])->name('guardians.confirmUser');
Route::get('guardians/{guardian}/user', [UserController::class, 'index'])->name('guardians.getUser');
Route::post('guardians/{guardian}/user', [UserController::class, 'create'])->name('guardians.createUser');
Route::patch('guardians/{guardian}/user', [UserController::class, 'update'])->name('guardians.updateUser');
Route::post('guardians/{guardian}/period', [UserController::class, 'updateCurrentPeriod'])->name('guardians.updateCurrentPeriod');

Route::post('guardians/{guardian}/photo', [PhotoController::class, 'upload'])
    ->name('guardians.uploadPhoto');

Route::delete('guardians/{guardian}/photo', [PhotoController::class, 'remove'])
    ->name('guardians.removePhoto');

Route::get('guardians/pre-requisite', [GuardianController::class, 'preRequisite'])->name('guardians.preRequisite');
Route::apiResource('guardians', GuardianController::class)->except(['store']);
