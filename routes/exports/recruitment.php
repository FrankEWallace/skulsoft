<?php

use App\Http\Controllers\Recruitment\ApplicationController;
use App\Http\Controllers\Recruitment\ApplicationExportController;
use App\Http\Controllers\Recruitment\VacancyController;
use App\Http\Controllers\Recruitment\VacancyExportController;
use Illuminate\Support\Facades\Route;

Route::prefix('recruitment')->name('recruitment.')->group(function () {
    Route::get('vacancies/{vacancy}/media/{uuid}', [VacancyController::class, 'downloadMedia']);
    Route::get('vacancies/export', VacancyExportController::class)->middleware('permission:job-vacancy:export');

    Route::get('applications/{application}/media/{uuid}', [ApplicationController::class, 'downloadMedia']);
    Route::get('applications/export', ApplicationExportController::class)->middleware('permission:job-application:export');
});
