<?php

use App\Http\Controllers\Blog\BlogController;
use App\Http\Controllers\Blog\BlogExportController;
use Illuminate\Support\Facades\Route;

Route::get('blogs/{blog}/media/{uuid}', [BlogController::class, 'downloadMedia']);
Route::get('blogs/export', BlogExportController::class)
    ->middleware('permission:blog:read')
    ->name('blogs.export');
