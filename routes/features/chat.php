<?php

use App\Http\Controllers\Chat\ChatController;
use App\Http\Controllers\Chat\MessageController;
use App\Http\Controllers\Chat\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('chat.enabled')->group(function () {
    Route::get('/users', UserController::class)->name('chat.users');

    Route::get('/', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/', [ChatController::class, 'store'])->name('chat.store');
    Route::get('/{chat}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/{chat}/read', [ChatController::class, 'markAsRead'])->name('chat.markAsRead');
    Route::delete('/{chat}', [ChatController::class, 'destroy'])->name('chat.destroy');

    Route::get('/{chat}/messages', [MessageController::class, 'index'])->name('chat.messages.index');
    Route::post('/{chat}/messages', [MessageController::class, 'store'])->name('chat.messages.store');
});
