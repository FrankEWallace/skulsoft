<?php

use App\Http\Controllers\GalleryExportController;
use Illuminate\Support\Facades\Route;

Route::get('galleries/export', GalleryExportController::class)->middleware('permission:gallery:export')->name('galleries.export');
