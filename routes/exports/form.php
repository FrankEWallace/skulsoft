<?php

use App\Http\Controllers\Form\FormController;
use App\Http\Controllers\Form\FormExportController;
use App\Http\Controllers\Form\FormSubmissionController;
use App\Http\Controllers\Form\FormSubmissionExportController;
use Illuminate\Support\Facades\Route;

Route::get('forms/{form}/media/{uuid}', [FormController::class, 'downloadMedia']);
Route::get('forms/{form}/submissions/{submission}/media/{uuid}', [FormSubmissionController::class, 'downloadMedia']);

Route::get('forms/{form}/submissions/export', FormSubmissionExportController::class)->middleware('permission:form:read')->name('forms.submissions.export');

Route::get('forms/export', FormExportController::class);
