<?php

use App\Http\Controllers\Discipline\IncidentController;
use Illuminate\Support\Facades\Route;

Route::name('discipline.')->prefix('discipline')->group(function () {
    Route::get('incidents/pre-requisite', [IncidentController::class, 'preRequisite']);
    Route::resource('incidents', IncidentController::class)->middleware('permission:incident:manage');
});
