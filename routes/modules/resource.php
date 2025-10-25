<?php

use App\Http\Controllers\Resource\AssignmentController;
use App\Http\Controllers\Resource\AssignmentEvaluationController;
use App\Http\Controllers\Resource\AssignmentSubmissionController;
use App\Http\Controllers\Resource\BookListController;
use App\Http\Controllers\Resource\DiaryController;
use App\Http\Controllers\Resource\DiaryPreviewController;
use App\Http\Controllers\Resource\DownloadController;
use App\Http\Controllers\Resource\LearningMaterialController;
use App\Http\Controllers\Resource\LessonPlanController;
use App\Http\Controllers\Resource\OnlineClassController;
use App\Http\Controllers\Resource\Report\DateWiseAssignmentController;
use App\Http\Controllers\Resource\Report\DateWiseLearningMaterialController;
use App\Http\Controllers\Resource\Report\DateWiseStudentDiaryController;
use App\Http\Controllers\Resource\SyllabusController;
use Illuminate\Support\Facades\Route;

// Reception Routes
Route::prefix('resource')->name('resource.')->group(function () {
    Route::get('book-lists/pre-requisite', [BookListController::class, 'preRequisite'])->name('book-lists.preRequisite');

    Route::get('online-classes/pre-requisite', [OnlineClassController::class, 'preRequisite'])->name('online-classes.preRequisite');
    Route::apiResource('online-classes', OnlineClassController::class);

    Route::get('assignments/pre-requisite', [AssignmentController::class, 'preRequisite'])->name('assignments.preRequisite');

    Route::post('assignments/{assignment}/evaluate', [AssignmentEvaluationController::class, 'store'])->name('assignments.evaluate');
    Route::apiResource('assignments.submissions', AssignmentSubmissionController::class)->only(['index', 'store'])->names('assignments.submissions');

    Route::apiResource('assignments', AssignmentController::class);

    Route::get('lesson-plans/pre-requisite', [LessonPlanController::class, 'preRequisite'])->name('lesson-plans.preRequisite');
    Route::apiResource('lesson-plans', LessonPlanController::class);

    Route::get('syllabuses/pre-requisite', [SyllabusController::class, 'preRequisite'])->name('syllabuses.preRequisite');
    Route::apiResource('syllabuses', SyllabusController::class);

    Route::get('learning-materials/pre-requisite', [LearningMaterialController::class, 'preRequisite'])->name('learning-materials.preRequisite');
    Route::apiResource('learning-materials', LearningMaterialController::class);

    Route::get('diaries/pre-requisite', [DiaryController::class, 'preRequisite'])->name('diaries.preRequisite');
    Route::get('diaries/preview', DiaryPreviewController::class);
    Route::apiResource('diaries', DiaryController::class);

    Route::get('downloads/pre-requisite', [DownloadController::class, 'preRequisite'])->name('downloads.preRequisite');
    Route::apiResource('downloads', DownloadController::class);
});

Route::prefix('resource/reports')->name('resource.reports.')->group(function () {
    Route::middleware('permission:resource:report')->group(function () {
        Route::get('date-wise-student-diary/pre-requisite', [DateWiseStudentDiaryController::class, 'preRequisite'])->name('date-wise-student-diary.preRequisite');
        Route::get('date-wise-student-diary', [DateWiseStudentDiaryController::class, 'fetch'])->name('date-wise-student-diary.fetch');

        Route::get('date-wise-assignment/pre-requisite', [DateWiseAssignmentController::class, 'preRequisite'])->name('date-wise-assignment.preRequisite');
        Route::get('date-wise-assignment', [DateWiseAssignmentController::class, 'fetch'])->name('date-wise-assignment.fetch');

        Route::get('date-wise-learning-material/pre-requisite', [DateWiseLearningMaterialController::class, 'preRequisite'])->name('date-wise-learning-material.preRequisite');
        Route::get('date-wise-learning-material', [DateWiseLearningMaterialController::class, 'fetch'])->name('date-wise-learning-material.fetch');
    });
});
