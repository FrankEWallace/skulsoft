<?php

use App\Http\Controllers\Custom\HeadWiseFeeSummaryController;
use App\Http\Controllers\Reports\ExamMarkController;
use App\Http\Controllers\Reports\StudentAttendanceController;
use App\Http\Controllers\Reports\StudentProfileController;
use App\Http\Controllers\Student\SiblingController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')->name('reports.')->group(function () {
    Route::view('/', 'reports.index')->name('index');

    Route::middleware(['permission:finance:report'])->group(function () {
        Route::view('finance', 'reports.finance.index')->name('finance');
        Route::get('finance/head-wise-fee-summary', HeadWiseFeeSummaryController::class)->name('finance.head-wise-fee-summary');
    });

    Route::middleware(['permission:student:report'])->group(function () {
        Route::view('student', 'reports.student.index')->name('student');
        Route::get('student/profile', StudentProfileController::class)->name('student.profile');
        Route::get('student/attendance', StudentAttendanceController::class)->name('student.attendance');
        Route::get('student/sibling', [SiblingController::class, 'export'])->name('student.sibling');
    });

    Route::middleware(['permission:exam:report'])->group(function () {
        Route::view('exam', 'reports.exam.index')->name('exam');
        Route::get('exam/mark', ExamMarkController::class)->name('exam.mark');
    });
});
