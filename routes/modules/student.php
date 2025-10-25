<?php

use App\Http\Controllers\Student\AbsenteeController;
use App\Http\Controllers\Student\AccountController;
use App\Http\Controllers\Student\AttendanceController;
use App\Http\Controllers\Student\CustomFeeController;
use App\Http\Controllers\Student\CustomFeeImportController;
use App\Http\Controllers\Student\DialogueController;
use App\Http\Controllers\Student\DocumentController;
use App\Http\Controllers\Student\EditRequestActionController;
use App\Http\Controllers\Student\EditRequestController;
use App\Http\Controllers\Student\FeeAllocationController;
use App\Http\Controllers\Student\FeeController;
use App\Http\Controllers\Student\FeePaymentImportController;
use App\Http\Controllers\Student\FeeRefundActionController;
use App\Http\Controllers\Student\FeeRefundController;
use App\Http\Controllers\Student\GuardianController;
use App\Http\Controllers\Student\HeadWisePaymentController;
use App\Http\Controllers\Student\HealthRecordController;
use App\Http\Controllers\Student\LeaveRequestController;
use App\Http\Controllers\Student\OnlinePaymentController;
use App\Http\Controllers\Student\PaymentController;
use App\Http\Controllers\Student\PhotoController;
use App\Http\Controllers\Student\ProfileEditRequestController;
use App\Http\Controllers\Student\PromotionController;
use App\Http\Controllers\Student\QualificationController;
use App\Http\Controllers\Student\RecordController;
use App\Http\Controllers\Student\RegistrationActionController;
use App\Http\Controllers\Student\RegistrationController;
use App\Http\Controllers\Student\RegistrationDocumentController;
use App\Http\Controllers\Student\RegistrationPaymentController;
use App\Http\Controllers\Student\RegistrationQualificationController;
use App\Http\Controllers\Student\Report\BatchWiseAttendanceController;
use App\Http\Controllers\Student\Report\DateWiseAttendanceController;
use App\Http\Controllers\Student\Report\SubjectWiseAttendanceController;
use App\Http\Controllers\Student\RollNumberController;
use App\Http\Controllers\Student\ServiceAllocationController;
use App\Http\Controllers\Student\ServiceRequestActionController;
use App\Http\Controllers\Student\ServiceRequestController;
use App\Http\Controllers\Student\SiblingController;
use App\Http\Controllers\Student\StudentActionController;
use App\Http\Controllers\Student\StudentController;
use App\Http\Controllers\Student\StudentImportController;
use App\Http\Controllers\Student\StudentImportHistoryController;
use App\Http\Controllers\Student\StudentWiseAttendanceController;
use App\Http\Controllers\Student\StudentWiseExamReportController;
use App\Http\Controllers\Student\StudentWiseSubjectController;
use App\Http\Controllers\Student\SubjectController;
use App\Http\Controllers\Student\TimesheetActionController;
use App\Http\Controllers\Student\TimesheetBatchController;
use App\Http\Controllers\Student\TimesheetController;
use App\Http\Controllers\Student\TransferController;
use App\Http\Controllers\Student\TransferMediaController;
use App\Http\Controllers\Student\TransferRequestActionController;
use App\Http\Controllers\Student\TransferRequestController;
use App\Http\Controllers\Student\UserController;
use Illuminate\Support\Facades\Route;

Route::name('student.')->prefix('student')->group(function () {
    Route::get('registrations/{registration}/payment-pre-requisite', [RegistrationPaymentController::class, 'preRequisite'])->name('registrations.paymentPreRequisite');
    Route::get('registrations/{registration}/action-pre-requisite', [RegistrationActionController::class, 'preRequisite'])->name('registrations.actionPreRequisite');
    Route::post('registrations/{registration}/skip-payment', [RegistrationPaymentController::class, 'skipPayment'])->name('registrations.skipPayment');
    Route::post('registrations/{registration}/payment', [RegistrationPaymentController::class, 'payment'])->name('registrations.payment');
    Route::post('registrations/{registration}/action', [RegistrationActionController::class, 'action'])->name('registrations.action');
    Route::delete('registrations/{registration}/payment/{uuid}', [RegistrationPaymentController::class, 'cancelPayment'])->name('registrations.cancelPayment');

    Route::get('registrations/{registration}/qualifications/pre-requisite', [RegistrationQualificationController::class, 'preRequisite'])->name('registrations.qualifications.preRequisite');
    Route::apiResource('registrations.qualifications', RegistrationQualificationController::class)->only(['store', 'show', 'update', 'destroy'])->names('registrations.qualifications');

    Route::get('registrations/{registration}/documents/pre-requisite', [RegistrationDocumentController::class, 'preRequisite'])->name('registrations.documents.preRequisite');
    Route::apiResource('registrations.documents', RegistrationDocumentController::class)->only(['store', 'show', 'update', 'destroy'])->names('registrations.documents');

    Route::post('registrations/{registration}/photo', [RegistrationActionController::class, 'uploadPhoto'])->name('registrations.uploadPhoto');
    Route::delete('registrations/{registration}/photo', [RegistrationActionController::class, 'removePhoto'])->name('registrations.removePhoto');

    Route::get('registrations/pre-requisite', [RegistrationController::class, 'preRequisite'])->name('registrations.preRequisite');
    Route::post('registrations/delete', [RegistrationController::class, 'destroyMultiple']);
    Route::post('registrations/assign', [RegistrationActionController::class, 'updateBulkAssignTo'])->name('registrations.updateBulkAssignTo');
    Route::post('registrations/stage', [RegistrationActionController::class, 'updateBulkStage'])->name('registrations.updateBulkStage');

    Route::post('registrations/{registration}/detail', [RegistrationController::class, 'updateDetail'])->name('registrations.updateDetail');
    Route::get('registrations/{registration}/guardians', [RegistrationController::class, 'showGuardians'])->name('registrations.showGuardians');
    Route::get('registrations/{registration}/qualifications', [RegistrationController::class, 'showQualifications'])->name('registrations.showQualifications');
    Route::get('registrations/{registration}/documents', [RegistrationController::class, 'showDocuments'])->name('registrations.showDocuments');
    Route::apiResource('registrations', RegistrationController::class);

    Route::get('roll-number/pre-requisite', [RollNumberController::class, 'preRequisite'])->name('roll-number.preRequisite');
    Route::get('roll-number/fetch', [RollNumberController::class, 'fetch'])->name('roll-number.fetch');
    Route::post('roll-number', [RollNumberController::class, 'store'])->name('roll-number.store');

    Route::get('health-record/pre-requisite', [HealthRecordController::class, 'preRequisite'])->name('health-record.preRequisite');
    Route::get('health-record/fetch', [HealthRecordController::class, 'fetch'])->name('health-record.fetch');
    Route::post('health-record', [HealthRecordController::class, 'store'])->name('health-record.store');

    Route::get('fee-allocation/pre-requisite', [FeeAllocationController::class, 'preRequisite'])->name('fee-allocation.preRequisite');
    Route::get('fee-allocation/fetch', [FeeAllocationController::class, 'fetch'])->name('fee-allocation.fetch');
    Route::post('fee-allocation', [FeeAllocationController::class, 'allocate'])->name('fee-allocation.allocate');
    Route::post('fee-allocation/fee-concession-type', [FeeAllocationController::class, 'allocateFeeConcessionType'])->name('fee-allocation.allocateFeeConcessionType');
    Route::post('fee-allocation/remove', [FeeAllocationController::class, 'remove'])->name('fee-allocation.remove');

    Route::get('service-allocation/pre-requisite', [ServiceAllocationController::class, 'preRequisite'])->name('service-allocation.preRequisite');
    Route::get('service-allocation/fetch', [ServiceAllocationController::class, 'fetch'])->name('service-allocation.fetch');
    Route::post('service-allocation', [ServiceAllocationController::class, 'allocate'])->name('service-allocation.allocate');
    Route::post('service-allocation/remove', [ServiceAllocationController::class, 'remove'])->name('service-allocation.remove');

    Route::get('promotion/pre-requisite', [PromotionController::class, 'preRequisite'])->name('promotion.preRequisite');
    Route::get('promotion/fetch', [PromotionController::class, 'fetch'])->name('promotion.fetch');
    Route::post('promotion', [PromotionController::class, 'store'])->name('promotion.promote');

    Route::get('edit-requests/pre-requisite', [EditRequestController::class, 'preRequisite'])->name('edit-requests.preRequisite');

    Route::post('edit-requests/{edit_request}/action', [EditRequestActionController::class, 'action']);

    Route::apiResource('edit-requests', EditRequestController::class)->only(['index', 'show']);

    Route::get('service-requests/pre-requisite', [ServiceRequestController::class, 'preRequisite'])->name('service-requests.preRequisite');

    Route::post('service-requests/{service_request}/status', [ServiceRequestActionController::class, 'updateStatus']);
    Route::apiResource('service-requests', ServiceRequestController::class);

    Route::get('leave-requests/pre-requisite', [LeaveRequestController::class, 'preRequisite'])->name('leave-requests.preRequisite');
    Route::apiResource('leave-requests', LeaveRequestController::class);

    Route::get('transfer-requests/pre-requisite', [TransferRequestController::class, 'preRequisite'])->name('transfer-requests.preRequisite');

    Route::post('transfer-requests/{transfer_request}/action', [TransferRequestActionController::class, 'action']);

    Route::apiResource('transfer-requests', TransferRequestController::class);

    Route::get('transfers/pre-requisite', [TransferController::class, 'preRequisite'])->name('transfers.preRequisite');

    Route::post('transfers/{transfer}/media', [TransferMediaController::class, 'store']);

    Route::apiResource('transfers', TransferController::class);

    Route::get('attendance/absentees/pre-requisite', [AbsenteeController::class, 'preRequisite'])->name('attendance.absentee.preRequisite');
    Route::get('attendance/absentees', [AbsenteeController::class, 'fetch'])->name('attendance.absentee.fetch');

    Route::get('attendance/pre-requisite', [AttendanceController::class, 'preRequisite'])->name('attendance.preRequisite');
    Route::get('attendance/fetch', [AttendanceController::class, 'fetch'])->name('attendance.fetch');
    Route::post('attendance/remove', [AttendanceController::class, 'remove'])->name('attendance.remove');
    Route::post('attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::post('attendance/send-notification', [AttendanceController::class, 'sendNotification'])->name('attendance.sendNotification');

    Route::get('timesheet/check', [TimesheetActionController::class, 'check'])->name('checkTimesheet');
    Route::post('timesheet/clock', [TimesheetActionController::class, 'clock'])
        ->name('clockTimesheet')
        ->middleware('throttle:timesheet');

    Route::get('timesheet/batch/pre-requisite', [TimesheetBatchController::class, 'preRequisite'])->name('timesheet.batch.preRequisite');
    Route::get('timesheet/batch/fetch', [TimesheetBatchController::class, 'fetch'])->name('timesheet.batch.fetch');
    Route::post('timesheet/batch', [TimesheetBatchController::class, 'store'])->name('timesheet.batch.store');

    Route::apiResource('timesheets', TimesheetController::class)->names('timesheets');

    Route::get('subject/pre-requisite', [SubjectController::class, 'preRequisite'])->name('subject.preRequisite');
    Route::get('subject/fetch', [SubjectController::class, 'fetch'])->name('subject.fetch');
    Route::post('subject', [SubjectController::class, 'store'])->name('subject.store');
});

Route::post('students/{student}/user/confirm', [UserController::class, 'confirm'])->name('students.confirmUser');
Route::get('students/{student}/user', [UserController::class, 'index'])->name('students.getUser');
Route::post('students/{student}/user', [UserController::class, 'create'])->name('students.createUser');
Route::patch('students/{student}/user', [UserController::class, 'update'])->name('students.updateUser');
Route::post('students/{student}/period', [UserController::class, 'updateCurrentPeriod'])->name('students.updateCurrentPeriod');

Route::post('students/{student}/photo', [PhotoController::class, 'upload'])
    ->name('students.uploadPhoto');

Route::delete('students/{student}/photo', [PhotoController::class, 'remove'])
    ->name('students.removePhoto');

Route::delete('students/{student}/admission', [RecordController::class, 'cancelAdmission'])
    ->name('students.cancelAdmission');
Route::delete('students/{student}/promotion', [RecordController::class, 'cancelPromotion'])
    ->name('students.cancelPromotion');
Route::post('students/{student}/default-period', [StudentActionController::class, 'setDefaultPeriod'])->name('students.setDefaultPeriod');

Route::get('students/{student}/guardians/pre-requisite', [GuardianController::class, 'preRequisite'])->name('students.guardians.preRequisite');
Route::apiResource('students.guardians', GuardianController::class);

Route::get('students/{student}/siblings/pre-requisite', [SiblingController::class, 'preRequisite'])->name('students.siblings.preRequisite');
Route::apiResource('students.siblings', SiblingController::class)->only(['index']);

Route::get('students/{student}/records/pre-requisite', [RecordController::class, 'preRequisite'])->name('students.records.preRequisite');
Route::apiResource('students.records', RecordController::class);

Route::get('students/{student}/fee/pre-requisite', [FeeController::class, 'preRequisite'])->name('students.fee.preRequisite');
Route::get('students/{student}/fee', [FeeController::class, 'fetchFee'])->name('students.fetchFee');
Route::get('students/{student}/sibling-fees', [FeeController::class, 'getSiblingFees'])->name('students.getSiblingFees');
Route::get('students/{student}/fee/list', [FeeController::class, 'listFee'])->name('students.listFee');
Route::get('students/{student}/fee/summary', [FeeController::class, 'getFeeSummary'])->name('students.getFeeSummary');
Route::get('students/{student}/fees', [FeeController::class, 'getStudentFees'])->name('students.getStudentFees');
Route::post('students/{student}/fee', [FeeController::class, 'setFee'])->name('students.setFee');
Route::patch('students/{student}/fee', [FeeController::class, 'updateFee'])->name('students.updateFee');
Route::delete('students/{student}/fee', [FeeController::class, 'resetFee'])->name('students.resetFee');
Route::post('students/{student}/fee/custom-concession', [FeeController::class, 'setCustomConcession'])->name('students.setCustomConcession');

Route::get('students/{student}/attendance', [StudentWiseAttendanceController::class, 'fetch'])->name('students.attendance.fetch');
Route::get('students/{student}/exam-report', [StudentWiseExamReportController::class, 'fetch'])->name('students.exam-report.fetch');
Route::get('students/{student}/subject', [StudentWiseSubjectController::class, 'fetch'])->name('students.subject.fetch');

Route::get('students/{student}/payment/pre-requisite', [PaymentController::class, 'preRequisite'])->name('students.feePayment.preRequisite');
Route::post('students/{student}/head-wise-payment', [HeadWisePaymentController::class, 'makePayment'])->name('students.makeHeadWisePayment');

Route::post('students/{student}/bank-transfer', [PaymentController::class, 'bankTransfer'])->name('students.bankTransfer');
Route::post('students/{student}/bank-transfers/{uuid}/action', [PaymentController::class, 'bankTransferAction'])->name('students.bankTransferAction');
Route::post('students/{student}/payment', [PaymentController::class, 'makePayment'])->name('students.makePayment');
Route::post('students/{student}/payment/initiate', [PaymentController::class, 'storeTempPayment'])->name('students.storeTempPayment');
Route::get('students/{student}/payment/{uuid}', [PaymentController::class, 'getPayment'])->name('students.getPayment');
Route::patch('students/{student}/payment/{uuid}', [PaymentController::class, 'updatePayment'])->name('students.updatePayment');
Route::post('students/{student}/cancel-payment/{uuid}', [PaymentController::class, 'cancelPayment'])->name('students.cancelPayment');

Route::post('students/{student}/online-payment/initiate', [OnlinePaymentController::class, 'initiate'])->name('students.initiatePayment');
Route::post('students/{student}/online-payment/complete', [OnlinePaymentController::class, 'complete'])->name('students.completePayment');
Route::post('students/{student}/online-payment/fail', [OnlinePaymentController::class, 'fail'])->name('students.failPayment');
Route::post('students/{student}/online-payment/{uuid}/status', [OnlinePaymentController::class, 'updatePaymentStatus'])->name('students.updatePaymentStatus');

Route::get('students/{student}/custom-fees/pre-requisite', [CustomFeeController::class, 'preRequisite'])->name('students.custom-fees.preRequisite');
Route::apiResource('students.custom-fees', CustomFeeController::class)->names('students.custom-fees');

Route::get('students/{student}/fee-refunds/pre-requisite', [FeeRefundController::class, 'preRequisite'])->name('students.fee-refunds.preRequisite');
Route::post('students/{student}/fee-refunds/{uuid}/cancel', [FeeRefundActionController::class, 'cancel'])->name('students.fee-refunds.cancel');
Route::apiResource('students.fee-refunds', FeeRefundController::class)->names('students.fee-refunds');

Route::get('students/{student}/dialogues/pre-requisite', [DialogueController::class, 'preRequisite'])->middleware('permission:student:dialogue')->name('students.dialogues.preRequisite');
Route::apiResource('students.dialogues', DialogueController::class)->names('students.dialogues')->middleware('permission:student:dialogue');

Route::get('students/{student}/accounts/pre-requisite', [AccountController::class, 'preRequisite'])->name('students.accounts.preRequisite');
Route::apiResource('students.accounts', AccountController::class)->names('students.accounts');

Route::get('students/{student}/documents/pre-requisite', [DocumentController::class, 'preRequisite'])->name('students.documents.preRequisite');
Route::apiResource('students.documents', DocumentController::class)->names('students.documents');

Route::get('students/{student}/qualifications/pre-requisite', [QualificationController::class, 'preRequisite'])->name('students.qualifications.preRequisite');
Route::apiResource('students.qualifications', QualificationController::class)->names('students.qualifications');

Route::post('students/{student}/tags', [StudentActionController::class, 'updateTags'])->name('students.tags');

Route::get('students/pre-requisite', [StudentController::class, 'preRequisite'])->name('students.preRequisite');
Route::get('students/list', [StudentController::class, 'list'])->name('students.list');
Route::get('students/list-all', [StudentController::class, 'listAll'])->name('students.listAll');
Route::post('students/import', StudentImportController::class)->middleware('permission:students:create')->name('students.import');
Route::get('students/import/history', [StudentImportHistoryController::class, 'index'])->middleware('permission:students:create')->name('students.importHistory');
Route::delete('students/import/history/{uuid}', [StudentImportHistoryController::class, 'destroy'])->middleware('role:admin')->name('students.importHistory.destroy');

Route::post('students/custom-fee-import', CustomFeeImportController::class)->middleware('permission:fee:set')->name('students.custom-fee-import');
Route::post('students/fee-payment-import', FeePaymentImportController::class)->middleware('permission:fee-payment:import')->name('students.fee-payment-import');

Route::get('students/{student}/edit-requests', [ProfileEditRequestController::class, 'index'])->name('students.editRequests.index');
Route::post('students/{student}/edit-requests', [ProfileEditRequestController::class, 'store'])->name('students.editRequests.store');
Route::get('students/{student}/edit-requests/{uuid}', [ProfileEditRequestController::class, 'show'])->name('students.editRequests.show');

Route::get('students/summary', [StudentController::class, 'summary'])->name('students.summary');
Route::post('students/tags', [StudentActionController::class, 'updateBulkTags'])->name('students.updateBulkTags');
Route::post('students/mentor', [StudentActionController::class, 'updateBulkMentor'])->name('students.updateBulkMentor');
Route::post('students/enrollment-type', [StudentActionController::class, 'updateBulkEnrollmentType'])->name('students.updateBulkEnrollmentType');
Route::post('students/enrollment-status', [StudentActionController::class, 'updateBulkEnrollmentStatus'])->name('students.updateBulkEnrollmentStatus');
Route::post('students/groups', [StudentActionController::class, 'updateBulkGroups'])->name('students.updateBulkGroups');
Route::apiResource('students', StudentController::class)->except(['store']);

Route::prefix('student/reports')->name('student.reports.')->group(function () {
    Route::middleware('permission:student:list-attendance')->group(function () {
        Route::get('date-wise-attendance/pre-requisite', [DateWiseAttendanceController::class, 'preRequisite'])->name('date-wise-attendance.preRequisite');
        Route::get('date-wise-attendance', [DateWiseAttendanceController::class, 'fetch'])->name('date-wise-attendance.fetch');

        Route::get('batch-wise-attendance/pre-requisite', [BatchWiseAttendanceController::class, 'preRequisite'])->name('batch-wise-attendance.preRequisite');
        Route::get('batch-wise-attendance', [BatchWiseAttendanceController::class, 'fetch'])->name('batch-wise-attendance.fetch');

        Route::get('subject-wise-attendance/pre-requisite', [SubjectWiseAttendanceController::class, 'preRequisite'])->name('subject-wise-attendance.preRequisite');
        Route::get('subject-wise-attendance', [SubjectWiseAttendanceController::class, 'fetch'])->name('subject-wise-attendance.fetch');
    });
});
