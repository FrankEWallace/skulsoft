<?php

use App\Http\Controllers\Site\BlockExportController;
use App\Http\Controllers\Site\MenuExportController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\PageExportController;
use Illuminate\Support\Facades\Route;

Route::prefix('site')->name('site.')->group(function () {
    Route::get('pages/{page}/media/{uuid}', [PageController::class, 'downloadMedia']);
    Route::get('pages/export', PageExportController::class)->middleware('permission:site:manage');

    Route::get('menus/export', MenuExportController::class)->middleware('permission:site:manage');

    Route::get('blocks/export', BlockExportController::class)->middleware('permission:site:manage');
});
