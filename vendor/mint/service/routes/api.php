<?php

use Illuminate\Support\Facades\Route;
use Mint\Service\Http\Controllers\InstallController;
use Mint\Service\Http\Controllers\ProductController;

Route::prefix('api/v1')->group(function () {
    Route::get('install/pre-requisite', [InstallController::class, 'preRequisite'])->name('install.preRequisite');
    Route::post('install/validate', [InstallController::class, 'store'])->name('install.validate');
    Route::post('install', [InstallController::class, 'store'])->name('install.store');

    Route::get('product/info', [ProductController::class, 'info'])->name('product.info');
    Route::get('product/confirm', [ProductController::class, 'confirm'])->name('product.confirm');
    Route::post('product/license', [ProductController::class, 'license'])->name('product.license');
    Route::post('product/update', [ProductController::class, 'update'])->name('product.update');
});
