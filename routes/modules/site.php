<?php

use App\Http\Controllers\Site\BlockActionController;
use App\Http\Controllers\Site\BlockController;
use App\Http\Controllers\Site\MenuActionController;
use App\Http\Controllers\Site\MenuController;
use App\Http\Controllers\Site\PageActionController;
use App\Http\Controllers\Site\PageController;
use Illuminate\Support\Facades\Route;

Route::prefix('site')->name('site.')->group(function () {
    Route::get('pages/pre-requisite', [PageController::class, 'preRequisite'])->name('pages.preRequisite');

    Route::post('pages/{page}/assets/{type}', [PageActionController::class, 'uploadAsset'])->name('pages.uploadAsset')->whereIn('type', ['cover', 'og']);
    Route::delete('pages/{page}/assets/{type}', [PageActionController::class, 'removeAsset'])->name('pages.removeAsset')->whereIn('type', ['cover', 'og']);

    Route::post('pages/{page}/blocks', [PageActionController::class, 'updateBlocks'])->name('pages.updateBlocks');
    Route::post('pages/{page}/slider', [PageActionController::class, 'updateSlider'])->name('pages.updateSlider');
    Route::post('pages/{page}/cta', [PageActionController::class, 'updateCTA'])->name('pages.updateCTA');
    Route::post('pages/{page}/meta', [PageActionController::class, 'updateMeta'])->name('pages.updateMeta');

    Route::apiResource('pages', PageController::class);

    Route::get('menus/pre-requisite', [MenuController::class, 'preRequisite'])->name('menus.preRequisite');
    Route::post('menus/reorder', [MenuActionController::class, 'reorder'])->name('menus.reorder');
    Route::post('menus/reorder-sub-menu', [MenuActionController::class, 'reorderSubMenu'])->name('menus.reorderSubMenu');
    Route::apiResource('menus', MenuController::class);

    Route::get('blocks/pre-requisite', [BlockController::class, 'preRequisite'])->name('blocks.preRequisite');

    Route::post('blocks/reorder', [BlockActionController::class, 'reorder'])->name('blocks.reorder');
    Route::post('blocks/{block}/assets/{type}', [BlockActionController::class, 'uploadAsset'])->name('blocks.uploadAsset')->whereIn('type', ['cover']);
    Route::delete('blocks/{block}/assets/{type}', [BlockActionController::class, 'removeAsset'])->name('blocks.removeAsset')->whereIn('type', ['cover']);

    Route::post('blocks/{block}/slider-images', [BlockActionController::class, 'uploadSliderImage'])->name('blocks.uploadSliderImage');

    Route::delete('blocks/{block}/slider-images/{image}', [BlockActionController::class, 'deleteSliderImage'])->name('blocks.deleteSliderImage');

    Route::apiResource('blocks', BlockController::class);
});
