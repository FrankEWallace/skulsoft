<?php

use App\Http\Controllers\Blog\BlogActionController;
use App\Http\Controllers\Blog\BlogController;
use Illuminate\Support\Facades\Route;

Route::prefix('blogs')->group(function () {
    Route::get('pre-requisite', [BlogController::class, 'preRequisite'])->name('blogs.preRequisite');

    Route::post('{blog}/assets/{type}', [BlogActionController::class, 'uploadAsset'])->name('blogs.uploadAsset')->whereIn('type', ['cover', 'og']);
    Route::delete('{blog}/assets/{type}', [BlogActionController::class, 'removeAsset'])->name('blogs.removeAsset')->whereIn('type', ['cover', 'og']);

    Route::post('{blog}/meta', [BlogActionController::class, 'updateMeta'])->name('blogs.updateMeta');

    Route::post('{blog}/archive', [BlogActionController::class, 'archive'])->name('blogs.archive');
    Route::post('{blog}/unarchive', [BlogActionController::class, 'unarchive'])->name('blogs.unarchive');

    Route::post('{blog}/pin', [BlogActionController::class, 'pin'])->name('blogs.pin');
    Route::post('{blog}/unpin', [BlogActionController::class, 'unpin'])->name('blogs.unpin');

    Route::post('archive', [BlogController::class, 'archiveMultiple'])->name('blogs.archiveMultiple');
    Route::post('unarchive', [BlogController::class, 'unarchiveMultiple'])->name('blogs.unarchiveMultiple');
    Route::post('delete', [BlogController::class, 'destroyMultiple'])->name('blogs.destroyMultiple');
});

Route::apiResource('blogs', BlogController::class);
