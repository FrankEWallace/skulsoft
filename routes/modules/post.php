<?php

use App\Http\Controllers\Post\PostActionController;
use App\Http\Controllers\Post\PostController;
use App\Http\Controllers\Post\PostImageController;
use Illuminate\Support\Facades\Route;

// Post Routes
Route::post('post/images', [PostImageController::class, 'store']);

Route::delete('post/images', [PostImageController::class, 'destroy']);

Route::post('posts/{post}/pin', [PostActionController::class, 'pin'])->name('posts.pin');
Route::post('posts/{post}/unpin', [PostActionController::class, 'unpin'])->name('posts.unpin');

Route::get('posts/pre-requisite', [PostController::class, 'preRequisite']);
Route::apiResource('posts', PostController::class);
