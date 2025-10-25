<?php

use App\Http\Controllers\Student\AccountController;
use App\Http\Controllers\Student\AccountExportController;
use App\Http\Controllers\Student\AttendanceExportController;
use App\Http\Controllers\Student\CustomFeeExportController;
use App\Http\Controllers\Student\DialogueController;
use App\Http\Controllers\Student\DialogueExportController;
use App\Http\Controllers\Student\DocumentController;
use App\Http\Controllers\Student\DocumentExportController;
use App\Http\Controllers\Student\EditRequestController;
use App\Http\Controllers\Student\EditRequestExportController;
use App\Http\Controllers\Student\FeeController;
use App\Http\Controllers\Student\FeeRefundExportController;
use App\Http\Controllers\Student\GuardianExportController;
use App\Http\Controllers\Student\LeaveRequestController;
use App\Http\Controllers\Student\LeaveRequestExportController;
use App\Http\Controllers\Student\ProfileEditRequestController;
use App\Http\Controllers\Student\QualificationController;
use App\Http\Controllers\Student\QualificationExportController;
use App\Http\Controllers\Student\RecordExportController;
use App\Http\Controllers\Student\RegistrationController;
use App\Http\Controllers\Student\RegistrationDocumentController;
use App\Http\Controllers\Student\RegistrationExportController;
use App\Http\Controllers\Student\RegistrationPaymentController;
use App\Http\Controllers\Student\RegistrationQualificationController;
use App\Http\Controllers\Student\Report\BatchWiseAttendanceExportController;
use App\Http\Controllers\Student\Report\DateWiseAttendanceExportController;
use App\Http\Controllers\Student\Report\SubjectWiseAttendanceExportController;
use App\Http\Controllers\Student\ServiceRequestController;
use App\Http\Controllers\Student\ServiceRequestExportController;
use App\Http\Controllers\Student\StudentController;
use App\Http\Controllers\Student\StudentExportController;
use App\Http\Controllers\Student\TimesheetExportController;
use App\Http\Controllers\Student\TransferController;
use App\Http\Controllers\Student\TransferExportController;
use App\Http\Controllers\Student\TransferRequestController;
use App\Http\Controllers\Student\TransferRequestExportController;
use Illuminate\Support\Facades\Route;

Route::name('student.')->prefix('student')->group(function () {
    Route::get('registrations/{registration}/qualifications/{qualification}/media/{uuid}', [RegistrationQualificationController::class, 'downloadMedia']);
    Route::get('registrations/{registration}/documents/{document}/media/{uuid}', [RegistrationDocumentController::class, 'downloadMedia']);

    Route::get('registrations/{registration}/media/{uuid}', [RegistrationController::class, 'downloadMedia']);
    Route::get('registrations/{registration}/export', [RegistrationController::class, 'export']);
    Route::get('registrations/export', RegistrationExportController::class)->middleware('permission:registration:export')->name('registrations.export');

    Route::get('registrations/{registration}/fee/{uuid}/export', [RegistrationPaymentController::class, 'exportFee']);

    Route::get('edit-requests/{edit_request}/media/{uuid}', [EditRequestController::class, 'downloadMedia']);
    Route::get('edit-requests/export', EditRequestExportController::class)->middleware('permission:student:edit-request-action')->name('edit-requests.export');

    Route::get('service-requests/{service_request}/media/{uuid}', [ServiceRequestController::class, 'downloadMedia']);
    Route::get('service-requests/export', ServiceRequestExportController::class)->middleware('permission:student:service-request')->name('service-requests.export');

    Route::get('leave-requests/{leave_request}/media/{uuid}', [LeaveRequestController::class, 'downloadMedia']);
    Route::get('leave-requests/export', LeaveRequestExportController::class)->middleware('permission:student:leave-request')->name('leave-requests.export');

    Route::get('transfer-requests/{transfer_request}/media/{uuid}', [TransferRequestController::class, 'downloadMedia']);
    Route::get('transfer-requests/export', TransferRequestExportController::class)->middleware('permission:student:transfer-request')->name('transfer-requests.export');

    Route::get('transfers/{student}/media/{uuid}', [TransferController::class, 'downloadMedia']);

    Route::get('transfers/export', TransferExportController::class)->middleware('permission:student:transfer')->name('transfers.export');

    Route::get('attendance/export', AttendanceExportController::class)->middleware('permission:student:list-attendance')->name('attendances.export');

    Route::get('timesheets/export', TimesheetExportController::class)->middleware('permission:student:manage-timesheet');

    Route::get('reports/date-wise-attendance/export', DateWiseAttendanceExportController::class)->middleware('permission:student:list-attendance')->name('student.reports.date-wise-attendance.export');

    Route::get('reports/batch-wise-attendance/export', BatchWiseAttendanceExportController::class)->middleware('permission:student:list-attendance')->name('student.reports.batch-wise-attendance.export');

    Route::get('reports/subject-wise-attendance/export', SubjectWiseAttendanceExportController::class)->middleware('permission:student:list-attendance')->name('student.reports.subject-wise-attendance.export');
});

Route::get('students/{student}/export', [StudentController::class, 'export']);

Route::get('students/{student}/fee/export', [FeeController::class, 'exportFee'])->name('students.fee.export');

Route::get('students/{student}/fee-groups/{feeGroup}/export', [FeeController::class, 'exportFeeGroup'])->name('students.fee-groups.export');

Route::get('students/{student}/installments/{installment}/export', [FeeController::class, 'exportFeeInstallment'])->name('students.fee-installments.export');

Route::get('students/{student}/custom-fees/export', CustomFeeExportController::class)->middleware('permission:student:export')->name('students.custom-fees.export');

Route::get('students/{student}/fee-refunds/export', FeeRefundExportController::class)->middleware('permission:student:export')->name('students.fee-refunds.export');

Route::get('students/{student}/dialogues/{dialogue}/media/{uuid}', [DialogueController::class, 'downloadMedia'])->middleware('permission:student:dialogue');
Route::get('students/{student}/dialogues/export', DialogueExportController::class)->middleware('permission:student:dialogue')->name('students.dialogues.export');

Route::get('students/{student}/accounts/{account}/media/{uuid}', [AccountController::class, 'downloadMedia']);
Route::get('students/{student}/accounts/export', AccountExportController::class)->middleware('permission:student:export')->name('students.accounts.export');

Route::get('students/{student}/documents/{document}/media/{uuid}', [DocumentController::class, 'downloadMedia']);
Route::get('students/{student}/documents/export', DocumentExportController::class)->middleware('permission:student:export')->name('students.documents.export');

Route::get('students/{student}/qualifications/{qualification}/media/{uuid}', [QualificationController::class, 'downloadMedia']);
Route::get('students/{student}/qualifications/export', QualificationExportController::class)->middleware('permission:student:export')->name('students.qualifications.export');

Route::get('students/{student}/guardians/export', GuardianExportController::class)->middleware('permission:student:export')->name('students.guardians.export');

Route::get('students/{student}/records/export', RecordExportController::class)->middleware('permission:student:export')->name('students.records.export');

Route::get('students/{student}/edit-requests/{edit_request}/media/{uuid}', [ProfileEditRequestController::class, 'downloadMedia']);

Route::get('students/export', StudentExportController::class)->middleware('permission:student:export')->name('students.export');
