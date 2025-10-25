<?php

use App\Http\Controllers\ContactExportController;
use Illuminate\Support\Facades\Route;

Route::get('contacts/export', ContactExportController::class)->middleware('permission:contact:export')->name('contacts.export');
