<?php

use App\Http\Controllers\Contact\PhotoController;
use App\Http\Controllers\Contact\UserController;
use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

Route::post('contacts/{contact}/user/confirm', [UserController::class, 'confirm'])->name('contacts.confirmUser');
Route::get('contacts/{contact}/user', [UserController::class, 'index'])->name('contacts.getUser');
Route::post('contacts/{contact}/user', [UserController::class, 'create'])->name('contacts.createUser');
Route::patch('contacts/{contact}/user', [UserController::class, 'update'])->name('contacts.updateUser');

Route::post('contacts/{contact}/photo', [PhotoController::class, 'upload'])
    ->name('contacts.uploadPhoto');

Route::delete('contacts/{contact}/photo', [PhotoController::class, 'remove'])
    ->name('contacts.removePhoto');

Route::get('contacts/pre-requisite', [ContactController::class, 'preRequisite'])->name('contacts.preRequisite');
Route::apiResource('contacts', ContactController::class);
