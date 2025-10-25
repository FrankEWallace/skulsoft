<?php

use App\Http\Controllers\Exam\AssessmentExportController;
use App\Http\Controllers\Exam\ExamExportController;
use App\Http\Controllers\Exam\FormExportController;
use App\Http\Controllers\Exam\GradeExportController;
use App\Http\Controllers\Exam\MarksheetPrintController;
use App\Http\Controllers\Exam\ObservationExportController;
use App\Http\Controllers\Exam\OnlineExamExportController;
use App\Http\Controllers\Exam\OnlineExamQuestionExportController;
use App\Http\Controllers\Exam\ScheduleExportController;
use App\Http\Controllers\Exam\TermExportController;
use Illuminate\Support\Facades\Route;

Route::prefix('exam')->name('exam.')->group(function () {
    Route::get('grades/export', GradeExportController::class)->middleware('permission:exam-grade:manage')->name('grades.export');

    Route::get('assessments/export', AssessmentExportController::class)->middleware('permission:exam-assessment:manage')->name('assessments.export');

    Route::get('observations/export', ObservationExportController::class)->middleware('permission:exam-observation:manage')->name('observations.export');

    Route::get('terms/export', TermExportController::class)->middleware('permission:exam-term:manage')->name('terms.export');

    Route::get('schedules/{schedule}/marksheet', [MarksheetPrintController::class, 'export'])->name('schedules.marksheet.print');

    Route::get('schedules/export', ScheduleExportController::class)->name('schedules.export');

    Route::get('online-exams/{onlineExam}/questions/export', OnlineExamQuestionExportController::class)->middleware('permission:online-exam:export')->name('online-exams.questions.export');

    Route::get('online-exams/export', OnlineExamExportController::class)->middleware('permission:online-exam:export')->name('online-exams.export');

    Route::get('forms/export', FormExportController::class)->name('forms.export');
});

Route::get('exams/export', ExamExportController::class)->middleware('permission:exam:manage')->name('exams.export');
