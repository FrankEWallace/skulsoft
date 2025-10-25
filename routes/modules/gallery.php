<?php

use App\Http\Controllers\GalleryActionController;
use App\Http\Controllers\GalleryController;
use Illuminate\Support\Facades\Route;

Route::get('galleries/pre-requisite', [GalleryController::class, 'preRequisite'])->name('galleries.preRequisite');

Route::post('galleries/{gallery}/upload', [GalleryActionController::class, 'upload'])->name('galleries.upload');

Route::post('galleries/{gallery}/images/{image}/cover', [GalleryActionController::class, 'makeCover'])->name('galleries.makeCover');

Route::delete('galleries/{gallery}/images/{image}', [GalleryActionController::class, 'deleteImage'])->name('galleries.deleteImage');

Route::apiResource('galleries', GalleryController::class);
