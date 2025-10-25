<?php

use App\Http\Controllers\Exam\AdmitCardController;
use App\Http\Controllers\Exam\AssessmentController;
use App\Http\Controllers\Exam\AttendanceController;
use App\Http\Controllers\Exam\CommentController;
use App\Http\Controllers\Exam\ExamActionController;
use App\Http\Controllers\Exam\ExamController;
use App\Http\Controllers\Exam\FormActionController;
use App\Http\Controllers\Exam\FormController;
use App\Http\Controllers\Exam\GradeController;
use App\Http\Controllers\Exam\MarkController;
use App\Http\Controllers\Exam\MarkImportController;
use App\Http\Controllers\Exam\MarksheetController;
use App\Http\Controllers\Exam\MarksheetPrintController;
use App\Http\Controllers\Exam\MarksheetProcessController;
use App\Http\Controllers\Exam\ObservationController;
use App\Http\Controllers\Exam\ObservationMarkController;
use App\Http\Controllers\Exam\OnlineExamActionController;
use App\Http\Controllers\Exam\OnlineExamController;
use App\Http\Controllers\Exam\OnlineExamQuestionActionController;
use App\Http\Controllers\Exam\OnlineExamQuestionController;
use App\Http\Controllers\Exam\OnlineExamSubmissionController;
use App\Http\Controllers\Exam\OnlineExamSubmitController;
use App\Http\Controllers\Exam\Report\ExamSummaryController;
use App\Http\Controllers\Exam\Report\MarkSummaryController;
use App\Http\Controllers\Exam\ScheduleActionController;
use App\Http\Controllers\Exam\ScheduleController;
use App\Http\Controllers\Exam\TermActionController;
use App\Http\Controllers\Exam\TermController;
use Illuminate\Support\Facades\Route;

// Exam Routes
Route::name('exam.')->prefix('exam')->group(function () {
    Route::get('grades/pre-requisite', [GradeController::class, 'preRequisite'])->name('grades.preRequisite')->middleware('permission:exam-grade:manage');
    Route::apiResource('grades', GradeController::class)->middleware('permission:exam-grade:manage');

    Route::get('assessments/pre-requisite', [AssessmentController::class, 'preRequisite'])->name('assessments.preRequisite')->middleware('permission:exam-assessment:manage');
    Route::apiResource('assessments', AssessmentController::class)->middleware('permission:exam-assessment:manage');

    Route::get('observations/pre-requisite', [ObservationController::class, 'preRequisite'])->name('observations.preRequisite')->middleware('permission:exam-observation:manage');
    Route::apiResource('observations', ObservationController::class)->middleware('permission:exam-observation:manage');

    Route::get('terms/pre-requisite', [TermController::class, 'preRequisite'])->name('terms.preRequisite')->middleware('permission:exam-term:manage');

    Route::post('terms/reorder', [TermActionController::class, 'reorder'])->name('terms.reorder');

    Route::apiResource('terms', TermController::class)->middleware('permission:exam-term:manage');

    Route::get('schedules/pre-requisite', [ScheduleController::class, 'preRequisite'])->name('schedules.preRequisite');

    Route::patch('schedules/{schedule}/toggle-publish-admit-card', [ScheduleActionController::class, 'togglePublishAdmitCard'])->name('exams.togglePublishAdmitCard');

    Route::patch('schedules/{schedule}/form', [ScheduleActionController::class, 'updateForm'])->name('schedules.updateForm');

    Route::post('schedules/{schedule}/form/confirm', [ScheduleActionController::class, 'confirmForm'])->name('schedules.confirmForm');
    Route::post('schedules/{schedule}/form', [ScheduleActionController::class, 'submitForm'])->name('schedules.submitForm');

    Route::post('schedules/{schedule}/copy', [ScheduleActionController::class, 'copyToCourse'])->name('schedules.copyToCourse');

    Route::apiResource('schedules', ScheduleController::class);

    Route::get('online-exams/pre-requisite', [OnlineExamController::class, 'preRequisite'])->name('online-exams.preRequisite');

    Route::get('online-exams/{onlineExam}/questions/pre-requisite', [OnlineExamQuestionController::class, 'preRequisite'])->name('online-exams.questions.preRequisite');

    Route::post('online-exams/{onlineExam}/questions/reorder', [OnlineExamQuestionActionController::class, 'reorder'])->name('online-exams.questions.reorder');

    Route::apiResource('online-exams.questions', OnlineExamQuestionController::class);

    Route::get('online-exams/{onlineExam}/submissions/{submission}/questions', [OnlineExamSubmissionController::class, 'getQuestions'])->name('online-exams.submissions.getQuestions');

    Route::post('online-exams/{onlineExam}/submissions/{submission}/evaluate', [OnlineExamSubmissionController::class, 'evaluate'])->name('online-exams.submissions.evaluate');

    Route::apiResource('online-exams.submissions', OnlineExamSubmissionController::class)->only(['index', 'destroy']);

    Route::get('online-exams/{onlineExam}/live-questions', [OnlineExamSubmitController::class, 'getQuestions'])->name('online-exams.submit.getQuestions');
    Route::post('online-exams/{onlineExam}/start', [OnlineExamSubmitController::class, 'startSubmission'])->name('online-exams.submit.start');
    Route::post('online-exams/{onlineExam}/submit', [OnlineExamSubmitController::class, 'submit'])->name('online-exams.submit');
    Route::post('online-exams/{onlineExam}/finish-submit', [OnlineExamSubmitController::class, 'finishSubmission'])->name('online-exams.submit.finish');
    Route::post('online-exams/{onlineExam}/status', [OnlineExamActionController::class, 'updateStatus'])->name('online-exams.updateStatus');

    Route::apiResource('online-exams', OnlineExamController::class);

    Route::get('forms/pre-requisite', [FormController::class, 'preRequisite'])->name('forms.preRequisite');

    Route::post('forms/{form}/status', [FormActionController::class, 'updateStatus'])->name('forms.updateStatus');

    Route::get('forms/{form}/print', [FormActionController::class, 'print'])->name('forms.print');

    Route::get('forms/{form}/print-admit-card', [FormActionController::class, 'printAdmitCard'])->name('forms.printAdmitCard');

    Route::apiResource('forms', FormController::class)->only(['index', 'show', 'destroy']);

    Route::get('mark/pre-requisite', [MarkController::class, 'preRequisite'])->name('mark.preRequisite');

    Route::post('mark/import', MarkImportController::class)->middleware('permission:exam:marks-record')->name('mark.import');

    Route::get('mark/fetch', [MarkController::class, 'fetch'])->name('mark.fetch');
    Route::post('mark', [MarkController::class, 'store'])->name('mark.store');
    Route::delete('mark', [MarkController::class, 'remove'])->name('mark.remove');

    Route::get('observation-mark/pre-requisite', [ObservationMarkController::class, 'preRequisite'])->name('observation-mark.preRequisite');
    Route::get('observation-mark/fetch', [ObservationMarkController::class, 'fetch'])->name('observation-mark.fetch');
    Route::post('observation-mark', [ObservationMarkController::class, 'store'])->name('observation-mark.store');
    Route::delete('observation-mark', [ObservationMarkController::class, 'remove'])->name('observation-mark.remove');

    Route::get('comment/pre-requisite', [CommentController::class, 'preRequisite'])->name('comment.preRequisite');
    Route::get('comment/fetch', [CommentController::class, 'fetch'])->name('comment.fetch');
    Route::post('comment', [CommentController::class, 'store'])->name('comment.store');
    Route::delete('comment', [CommentController::class, 'remove'])->name('comment.remove');

    Route::get('attendance/pre-requisite', [AttendanceController::class, 'preRequisite'])->name('attendance.preRequisite');
    Route::get('attendance/fetch', [AttendanceController::class, 'fetch'])->name('attendance.fetch');
    Route::post('attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::delete('attendance', [AttendanceController::class, 'remove'])->name('attendance.remove');

    Route::middleware('permission:exam-admit-card:access')->group(function () {
        Route::get('admit-card/pre-requisite', [AdmitCardController::class, 'preRequisite'])->name('admit-card.preRequisite');
        Route::get('admit-card', [AdmitCardController::class, 'fetchReport'])->name('admit-card.fetchReport');
    });

    Route::middleware('permission:exam-marksheet:access')->group(function () {
        Route::get('marksheet/pre-requisite', [MarksheetController::class, 'preRequisite'])->name('marksheet.preRequisite');
        Route::get('marksheet', [MarksheetController::class, 'fetchReport'])->name('marksheet.fetchReport');
    });

    Route::middleware('permission:exam-marksheet:access')->group(function () {
        Route::get('marksheet/process/pre-requisite', [MarksheetProcessController::class, 'preRequisite'])->name('marksheet.process.preRequisite');
        Route::get('marksheet/process', [MarksheetProcessController::class, 'process'])->name('marksheet.process');
    });

    Route::middleware('permission:exam-marksheet:access')->group(function () {
        Route::get('marksheet/print/pre-requisite', [MarksheetPrintController::class, 'preRequisite'])->name('marksheet.print.preRequisite');
        Route::get('marksheet/print', [MarksheetPrintController::class, 'print'])->name('marksheet.print');
    });

    Route::prefix('reports')->name('reports.')->middleware('permission:exam:report')->group(function () {
        Route::get('mark-summary/pre-requisite', [MarkSummaryController::class, 'preRequisite'])->name('mark-summary.preRequisite');
        Route::get('mark-summary', [MarkSummaryController::class, 'fetchReport'])->name('mark-summary.fetchReport');

        Route::get('exam-summary/pre-requisite', [ExamSummaryController::class, 'preRequisite'])->name('exam-summary.preRequisite');
        Route::get('exam-summary', [ExamSummaryController::class, 'fetchReport'])->name('exam-summary.fetchReport');
    });
});

Route::middleware('permission:exam:manage')->group(function () {
    Route::get('exams/pre-requisite', [ExamController::class, 'preRequisite'])->name('exams.preRequisite');

    Route::post('exams/{exam}/config', [ExamActionController::class, 'storeConfig'])->name('exams.storeConfig');

    Route::post('exams/reorder', [ExamActionController::class, 'reorder'])->name('exams.reorder');

    Route::apiResource('exams', ExamController::class);
});
