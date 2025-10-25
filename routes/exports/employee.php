<?php

use App\Http\Controllers\Employee\AccountController;
use App\Http\Controllers\Employee\AccountExportController;
use App\Http\Controllers\Employee\Attendance\AttendanceExportController;
use App\Http\Controllers\Employee\Attendance\TimesheetExportController;
use App\Http\Controllers\Employee\Attendance\TypeExportController as AttendanceTypeExportController;
use App\Http\Controllers\Employee\Attendance\WorkShiftExportController;
use App\Http\Controllers\Employee\DepartmentExportController;
use App\Http\Controllers\Employee\DesignationExportController;
use App\Http\Controllers\Employee\DialogueController;
use App\Http\Controllers\Employee\DialogueExportController;
use App\Http\Controllers\Employee\DocumentController;
use App\Http\Controllers\Employee\DocumentExportController;
use App\Http\Controllers\Employee\EditRequestController;
use App\Http\Controllers\Employee\EditRequestExportController;
use App\Http\Controllers\Employee\EmployeeExportController;
use App\Http\Controllers\Employee\ExperienceController;
use App\Http\Controllers\Employee\ExperienceExportController;
use App\Http\Controllers\Employee\InchargeExportController;
use App\Http\Controllers\Employee\Leave\AllocationExportController as LeaveAllocationExportController;
use App\Http\Controllers\Employee\Leave\RequestController as LeaveRequestController;
use App\Http\Controllers\Employee\Leave\RequestExportController as LeaveRequestExportController;
use App\Http\Controllers\Employee\Leave\TypeExportController as LeaveTypeExportController;
use App\Http\Controllers\Employee\Payroll\BulkExportPayrollController;
use App\Http\Controllers\Employee\Payroll\PayHeadExportController;
use App\Http\Controllers\Employee\Payroll\PaymentAdviceController;
use App\Http\Controllers\Employee\Payroll\PayrollController;
use App\Http\Controllers\Employee\Payroll\PayrollExportController;
use App\Http\Controllers\Employee\Payroll\SalarySheetController;
use App\Http\Controllers\Employee\Payroll\SalaryStructureExportController;
use App\Http\Controllers\Employee\Payroll\SalaryTemplateExportController;
use App\Http\Controllers\Employee\ProfileEditRequestController;
use App\Http\Controllers\Employee\QualificationController;
use App\Http\Controllers\Employee\QualificationExportController;
use App\Http\Controllers\Employee\RecordController;
use App\Http\Controllers\Employee\WorkShiftExportController as EmployeeWorkShiftExportController;
use Illuminate\Support\Facades\Route;

Route::name('employee.')->prefix('employee')->group(function () {
    Route::get('departments/export', DepartmentExportController::class)->middleware('permission:department:export')->name('departments.export');

    Route::get('designations/export', DesignationExportController::class)->middleware('permission:designation:export')->name('designations.export');

    Route::get('leave/types/export', LeaveTypeExportController::class)->middleware('permission:leave:config')->name('leaveTypes.export');

    Route::get('leave/allocations/export', LeaveAllocationExportController::class)->middleware('permission:leave-allocation:export');

    Route::get('leave/requests/{leave_request}/media/{uuid}', [LeaveRequestController::class, 'downloadMedia']);

    Route::get('leave/requests/export', LeaveRequestExportController::class)->middleware('permission:leave-request:export');

    Route::get('attendance/types/export', AttendanceTypeExportController::class)->middleware('permission:attendance:config')->name('attendanceTypes.export');
    Route::get('attendance/export', AttendanceExportController::class)->middleware('permission:attendance:export')->name('attendances.export');

    Route::get('attendance/timesheets/export', TimesheetExportController::class)->middleware('permission:timesheet:export');

    Route::get('attendance/work-shifts/export', WorkShiftExportController::class)->middleware('permission:work-shift:export');

    Route::get('payrolls/export', PayrollExportController::class)->middleware('permission:payroll:export');

    Route::get('payrolls/salary-sheet/export', SalarySheetController::class)->middleware('permission:payroll:export');
    Route::get('payrolls/payment-advice/export', PaymentAdviceController::class)->middleware('permission:payroll:export');
    Route::get('payrolls/bulk-export', BulkExportPayrollController::class)->middleware('permission:payroll:export');
    Route::get('payrolls/{payroll}/export', [PayrollController::class, 'export'])->middleware('permission:payroll:export');

    Route::get('payroll/pay-heads/export', PayHeadExportController::class)->middleware('permission:payroll:config')->name('payHeads.export');
    Route::get('payroll/salary-templates/export', SalaryTemplateExportController::class)->middleware('permission:salary-template:export');
    Route::get('payroll/salary-structures/export', SalaryStructureExportController::class)->middleware('permission:salary-structure:export');

    Route::get('edit-requests/{edit_request}/media/{uuid}', [EditRequestController::class, 'downloadMedia']);
    Route::get('edit-requests/export', EditRequestExportController::class)->middleware('permission:employee:edit-request-action')->name('edit-requests.export');
});

Route::prefix('employees')->group(function () {
    Route::get('{employee}/records/{record}/media/{uuid}', [RecordController::class, 'downloadMedia']);
    Route::get('{employee}/work-shifts/export', EmployeeWorkShiftExportController::class)->middleware('permission:work-shift:assign')->name('employees.work-shifts.export');
    Route::get('{employee}/qualifications/{qualification}/media/{uuid}', [QualificationController::class, 'downloadMedia']);
    Route::get('{employee}/dialogues/{dialogue}/media/{uuid}', [DialogueController::class, 'downloadMedia'])->middleware('permission:employee:dialogue');
    Route::get('{employee}/accounts/{account}/media/{uuid}', [AccountController::class, 'downloadMedia']);
    Route::get('{employee}/documents/{document}/media/{uuid}', [DocumentController::class, 'downloadMedia']);
    Route::get('{employee}/experiences/{experience}/media/{uuid}', [ExperienceController::class, 'downloadMedia']);

    // Route::get('{employee}/records/export', RecordExportController::class)->middleware('permission:employment-record:manage')->name('employees.records.export');
    Route::get('{employee}/incharges/export', InchargeExportController::class)->middleware('permission:employee:export')->name('employees.incharges.export');
    Route::get('{employee}/qualifications/export', QualificationExportController::class)->middleware('permission:employee:export')->name('employees.qualifications.export');
    Route::get('{employee}/dialogues/export', DialogueExportController::class)->middleware('permission:employee:dialogue')->name('employees.dialogues.export');
    Route::get('{employee}/accounts/export', AccountExportController::class)->middleware('permission:employee:export')->name('employees.accounts.export');
    Route::get('{employee}/documents/export', DocumentExportController::class)->middleware('permission:employee:export')->name('employees.documents.export');
    Route::get('{employee}/experiences/export', ExperienceExportController::class)->middleware('permission:employee:export')->name('employees.experiences.export');

    Route::get('{employee}/edit-requests/{edit_request}/media/{uuid}', [ProfileEditRequestController::class, 'downloadMedia']);

    Route::get('export', EmployeeExportController::class)->middleware('permission:employee:export')->name('employees.export');
});
