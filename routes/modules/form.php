<?php

use App\Http\Controllers\Form\FormActionController;
use App\Http\Controllers\Form\FormController;
use App\Http\Controllers\Form\FormSubmissionController;
use App\Http\Controllers\Form\FormSubmitController;
use Illuminate\Support\Facades\Route;

// Form Routes
Route::get('forms/pre-requisite', [FormController::class, 'preRequisite'])->name('forms.preRequisite');

Route::post('forms/{form}/status', [FormActionController::class, 'updateStatus'])->name('forms.updateStatus');

Route::get('forms/{form}/detail', [FormController::class, 'detail'])->name('forms.detail');

Route::post('forms/{form}/submit', FormSubmitController::class)->name('forms.submit');

Route::apiResource('forms.submissions', FormSubmissionController::class)->only(['index', 'show', 'destroy'])->middleware('permission:form-submission:manage');

Route::apiResource('forms', FormController::class);
