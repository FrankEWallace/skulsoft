<?php

use App\Http\Controllers\Resource\AssignmentController;
use App\Http\Controllers\Resource\AssignmentExportController;
use App\Http\Controllers\Resource\AssignmentSubmissionController;
use App\Http\Controllers\Resource\BookListController;
use App\Http\Controllers\Resource\DiaryController;
use App\Http\Controllers\Resource\DiaryExportController;
use App\Http\Controllers\Resource\DownloadController;
use App\Http\Controllers\Resource\DownloadExportController;
use App\Http\Controllers\Resource\LearningMaterialController;
use App\Http\Controllers\Resource\LearningMaterialExportController;
use App\Http\Controllers\Resource\LessonPlanController;
use App\Http\Controllers\Resource\LessonPlanExportController;
use App\Http\Controllers\Resource\OnlineClassController;
use App\Http\Controllers\Resource\OnlineClassExportController;
use App\Http\Controllers\Resource\Report\DateWiseAssignmentController;
use App\Http\Controllers\Resource\Report\DateWiseLearningMaterialController;
use App\Http\Controllers\Resource\Report\DateWiseStudentDiaryController;
use App\Http\Controllers\Resource\SyllabusController;
use App\Http\Controllers\Resource\SyllabusExportController;
use Illuminate\Support\Facades\Route;

Route::prefix('resource')->name('resource.')->group(function () {
    Route::get('book-lists/{course}', [BookListController::class, 'download']);

    Route::get('online-classes/{online_class}/media/{uuid}', [OnlineClassController::class, 'downloadMedia']);
    Route::get('online-classes/export', OnlineClassExportController::class)->middleware('permission:online-class:export');

    Route::get('assignments/{assignment}/media/{uuid}', [AssignmentController::class, 'downloadMedia']);
    Route::get('assignments/export', AssignmentExportController::class)->middleware('permission:assignment:export');

    Route::get('assignments/{assignment}/submissions/{submission}/media/{uuid}', [AssignmentSubmissionController::class, 'downloadMedia']);

    Route::get('lesson-plans/{lesson_plan}/media/{uuid}', [LessonPlanController::class, 'downloadMedia']);
    Route::get('lesson-plans/export', LessonPlanExportController::class)->middleware('permission:lesson-plan:export');

    Route::get('syllabuses/{syllabus}/media/{uuid}', [SyllabusController::class, 'downloadMedia']);
    Route::get('syllabuses/export', SyllabusExportController::class)->middleware('permission:syllabus:export');

    Route::get('learning-materials/{learning_material}/media/{uuid}', [LearningMaterialController::class, 'downloadMedia']);
    Route::get('learning-materials/export', LearningMaterialExportController::class)->middleware('permission:learning-material:export');

    Route::get('diaries/{diary}/media/{uuid}', [DiaryController::class, 'downloadMedia']);
    Route::get('diaries/export', DiaryExportController::class)->middleware('permission:student-diary:export')->name('diaries.export');

    Route::get('downloads/{download}/media/{uuid}', [DownloadController::class, 'downloadMedia']);
    Route::get('downloads/export', DownloadExportController::class)->middleware('permission:download:export');

    Route::get('reports/date-wise-student-diary/export', [DateWiseStudentDiaryController::class, 'export'])->middleware('permission:resource:report')->name('student.reports.date-wise-student-diary.export');
    Route::get('reports/date-wise-learning-material/export', [DateWiseLearningMaterialController::class, 'export'])->middleware('permission:resource:report')->name('student.reports.date-wise-learning-material.export');
    Route::get('reports/date-wise-assignment/export', [DateWiseAssignmentController::class, 'export'])->middleware('permission:resource:report')->name('student.reports.date-wise-assignment.export');
});
