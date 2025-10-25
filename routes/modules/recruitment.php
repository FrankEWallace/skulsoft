<?php

use App\Http\Controllers\Recruitment\ApplicationController;
use App\Http\Controllers\Recruitment\VacancyController;
use Illuminate\Support\Facades\Route;

// Recruitment Routes
Route::prefix('recruitment')->name('recruitment.')->group(function () {
    Route::get('vacancies/pre-requisite', [VacancyController::class, 'preRequisite'])->name('vacancies.preRequisite');
    Route::apiResource('vacancies', VacancyController::class);

    Route::get('applications/pre-requisite', [ApplicationController::class, 'preRequisite'])->name('applications.preRequisite');
    Route::apiResource('applications', ApplicationController::class);
});
