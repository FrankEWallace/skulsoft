<?php

use App\Http\Controllers\Approval\Report\RequestSummaryController;
use App\Http\Controllers\Approval\RequestActionController;
use App\Http\Controllers\Approval\RequestController;
use App\Http\Controllers\Approval\TypeController;
use Illuminate\Support\Facades\Route;

// Approval Routes
Route::prefix('approval')->name('approval.')->group(function () {
    Route::get('types/pre-requisite', [TypeController::class, 'preRequisite'])->name('types.preRequisite');
    Route::apiResource('types', TypeController::class);

    Route::get('requests/pre-requisite', [RequestController::class, 'preRequisite'])->name('requests.preRequisite');

    Route::get('requests/{approval_request}/action/pre-requisite', [RequestActionController::class, 'preRequisite'])->name('requests.action.preRequisite');
    Route::post('requests/{approval_request}/status', [RequestActionController::class, 'updateStatus'])->name('requests.action');
    Route::post('requests/{approval_request}/cancel', [RequestActionController::class, 'cancel'])->name('requests.cancel');

    Route::post('requests/{approval_request}/media', [RequestActionController::class, 'uploadMedia'])->name('requests.uploadMedia');
    Route::delete('requests/{approval_request}/media/{uuid}', [RequestActionController::class, 'removeMedia'])->name('requests.removeMedia');

    Route::apiResource('requests', RequestController::class);

    Route::prefix('reports')->name('reports.')->middleware('permission:approval:report')->group(function () {
        Route::get('request-summary/pre-requisite', [RequestSummaryController::class, 'preRequisite'])->name('request-summary.preRequisite');
        Route::get('request-summary', [RequestSummaryController::class, 'fetch'])->name('request-summary.fetch');
    });
});
