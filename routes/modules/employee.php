<?php

use App\Http\Controllers\Employee\AccountActionController;
use App\Http\Controllers\Employee\AccountController;
use App\Http\Controllers\Employee\Attendance\AttendanceController;
use App\Http\Controllers\Employee\Attendance\TimesheetActionController;
use App\Http\Controllers\Employee\Attendance\TimesheetController;
use App\Http\Controllers\Employee\Attendance\TimesheetImportController;
use App\Http\Controllers\Employee\Attendance\TypeController as AttendanceTypeController;
use App\Http\Controllers\Employee\Attendance\WorkShiftAssignController;
use App\Http\Controllers\Employee\Attendance\WorkShiftController;
use App\Http\Controllers\Employee\DepartmentController;
use App\Http\Controllers\Employee\DepartmentImportController;
use App\Http\Controllers\Employee\DesignationController;
use App\Http\Controllers\Employee\DesignationImportController;
use App\Http\Controllers\Employee\DialogueController;
use App\Http\Controllers\Employee\DocumentActionController;
use App\Http\Controllers\Employee\DocumentController;
use App\Http\Controllers\Employee\EditRequestActionController;
use App\Http\Controllers\Employee\EditRequestController;
use App\Http\Controllers\Employee\EmployeeActionController;
use App\Http\Controllers\Employee\EmployeeController;
use App\Http\Controllers\Employee\EmployeeImportController;
use App\Http\Controllers\Employee\ExperienceActionController;
use App\Http\Controllers\Employee\ExperienceController;
use App\Http\Controllers\Employee\InchargeController;
// use App\Http\Controllers\Employee\DesignationImportController;
use App\Http\Controllers\Employee\Leave\AllocationController as LeaveAllocationController;
use App\Http\Controllers\Employee\Leave\RequestActionController as LeaveRequestActionController;
use App\Http\Controllers\Employee\Leave\RequestController as LeaveRequestController;
use App\Http\Controllers\Employee\Leave\TypeController as LeaveTypeController;
use App\Http\Controllers\Employee\Payroll\PayHeadActionController;
use App\Http\Controllers\Employee\Payroll\PayHeadController;
use App\Http\Controllers\Employee\Payroll\PayrollController;
use App\Http\Controllers\Employee\Payroll\PayrollProcessController;
use App\Http\Controllers\Employee\Payroll\SalaryStructureController;
use App\Http\Controllers\Employee\Payroll\SalaryTemplateController;
use App\Http\Controllers\Employee\PhotoController;
use App\Http\Controllers\Employee\ProfileEditRequestController;
use App\Http\Controllers\Employee\QualificationActionController;
use App\Http\Controllers\Employee\QualificationController;
use App\Http\Controllers\Employee\RecordController;
use App\Http\Controllers\Employee\UserController;
use App\Http\Controllers\Employee\WorkShiftController as EmployeeWorkShiftController;
use Illuminate\Support\Facades\Route;

// Employee Routes
Route::name('employee.')->prefix('employee')->group(function () {
    Route::get('departments/pre-requisite', [DepartmentController::class, 'preRequisite'])->name('departments.preRequisite');
    Route::post('departments/import', DepartmentImportController::class)->middleware('permission:department:create');
    Route::apiResource('departments', DepartmentController::class);

    Route::get('designations/pre-requisite', [DesignationController::class, 'preRequisite'])->name('designations.preRequisite');
    Route::post('designations/import', DesignationImportController::class)->middleware('permission:designation:create');
    Route::apiResource('designations', DesignationController::class);

    Route::name('leave.')->prefix('leave')->group(function () {
        Route::get('types/pre-requisite', [LeaveTypeController::class, 'preRequisite'])->middleware('permission:leave:config')->name('types.preRequisite');
        Route::apiResource('types', LeaveTypeController::class)->parameters(['types' => 'leave_type'])->middleware('permission:leave:config')->names('types');

        Route::get('allocations/pre-requisite', [LeaveAllocationController::class, 'preRequisite'])->name('allocations.preRequisite');
        Route::get('allocations/{leave_allocation}/leave-requests', [LeaveAllocationController::class, 'fetchLeaveRequests'])->name('allocations.fetchLeaveRequests');
        Route::apiResource('allocations', LeaveAllocationController::class)->parameters(['allocations' => 'leave_allocation'])->names('allocations');

        Route::post('requests/{leave_request}/undo', [LeaveRequestActionController::class, 'undoStatus']);
        Route::post('requests/{leave_request}/status', [LeaveRequestActionController::class, 'updateStatus']);
        Route::get('requests/pre-requisite', [LeaveRequestController::class, 'preRequisite'])->name('requests.preRequisite');
        Route::apiResource('requests', LeaveRequestController::class)->parameters(['requests' => 'leave_request'])->names('requests');
    });

    Route::prefix('attendance')->group(function () {
        Route::get('types/pre-requisite', [AttendanceTypeController::class, 'preRequisite'])->middleware('permission:attendance:config')->name('attendanceTypes.preRequisite');
        Route::apiResource('types', AttendanceTypeController::class)->parameters(['types' => 'attendance_type'])->middleware('permission:attendance:config')->names('attendanceTypes');

        Route::get('pre-requisite', [AttendanceController::class, 'preRequisite'])->name('attendances.preRequisite');
        Route::get('list', [AttendanceController::class, 'list'])->name('attendances.list');
        Route::get('fetch', [AttendanceController::class, 'fetch'])->name('attendances.fetch');
        Route::post('mark', [AttendanceController::class, 'mark'])->name('attendances.mark');
        Route::get('production', [AttendanceController::class, 'fetchProduction'])->name('attendances.fetchProduction');
        Route::post('production', [AttendanceController::class, 'markProduction'])->name('attendances.markProduction');

        Route::get('timesheet/check', [TimesheetActionController::class, 'check'])->name('attendances.checkTimesheet');
        Route::post('timesheet/clock', [TimesheetActionController::class, 'clock'])
            ->name('attendances.clockTimesheet')
            ->middleware('throttle:timesheet');
        Route::post('timesheet/sync', [TimesheetActionController::class, 'sync'])
            ->name('attendances.syncTimesheet')
            ->middleware('permission:timesheet:sync');

        Route::post('timesheets/import', TimesheetImportController::class)->middleware('permission:timesheet:import');
        Route::apiResource('timesheets', TimesheetController::class)->names('timesheets');

        Route::get('work-shift/assign/pre-requisite', [WorkShiftAssignController::class, 'preRequisite'])->name('workShifts.assign.preRequisite');
        Route::get('work-shift/assign/fetch', [WorkShiftAssignController::class, 'fetch'])->name('workShifts.assign.fetch');
        Route::post('work-shift/assign', [WorkShiftAssignController::class, 'assign'])->name('workShifts.assign');

        Route::get('work-shifts/pre-requisite', [WorkShiftController::class, 'preRequisite'])->name('workShifts.preRequisite');
        Route::apiResource('work-shifts', WorkShiftController::class)->names('workShifts');
    });

    Route::name('payroll.')->prefix('payroll')->group(function () {
        Route::get('pay-heads/pre-requisite', [PayHeadController::class, 'preRequisite'])->middleware('permission:payroll:config')->name('payHeads.preRequisite');

        Route::post('pay-heads/reorder', [PayHeadActionController::class, 'reorder'])->middleware('permission:payroll:config')->name('payHeads.reorder');

        Route::apiResource('pay-heads', PayHeadController::class)->middleware('permission:payroll:config')->names('payHeads');

        Route::get('salary-templates/pre-requisite', [SalaryTemplateController::class, 'preRequisite'])->name('salaryTemplates.preRequisite');
        Route::apiResource('salary-templates', SalaryTemplateController::class)->names('salaryTemplates');

        Route::get('salary-structures/pre-requisite', [SalaryStructureController::class, 'preRequisite'])->name('salaryStructures.preRequisite');
        Route::apiResource('salary-structures', SalaryStructureController::class)->names('salaryStructures');
    });

    Route::get('payrolls/fetch', [PayrollController::class, 'fetch'])->name('payrolls.fetch');
    Route::post('payrolls/process', [PayrollProcessController::class, 'bulkProcess'])->name('payrolls.bulkProcess');
    Route::post('payrolls/{payroll}/process', [PayrollProcessController::class, 'process'])->name('payrolls.process');
    Route::get('payrolls/pre-requisite', [PayrollController::class, 'preRequisite'])->name('payrolls.preRequisite');
    Route::apiResource('payrolls', PayrollController::class)->names('payrolls');

    Route::get('edit-requests/pre-requisite', [EditRequestController::class, 'preRequisite'])->name('edit-requests.preRequisite');

    Route::post('edit-requests/{edit_request}/action', [EditRequestActionController::class, 'action']);

    Route::apiResource('edit-requests', EditRequestController::class)->only(['index', 'show']);
});

Route::middleware('permission:employee:read')->group(function () {
    Route::post('employees/{employee}/user/confirm', [UserController::class, 'confirm'])->name('employees.confirmUser');
    Route::get('employees/{employee}/user', [UserController::class, 'index'])->name('employees.getUser');
    Route::post('employees/{employee}/user', [UserController::class, 'create'])->name('employees.createUser');
    Route::patch('employees/{employee}/user', [UserController::class, 'update'])->name('employees.updateUser');
    Route::post('employees/{employee}/period', [UserController::class, 'updateCurrentPeriod'])->name('employees.updateCurrentPeriod');

    Route::post('employees/{employee}/photo', [PhotoController::class, 'upload'])
        ->name('employees.uploadPhoto');

    Route::delete('employees/{employee}/photo', [PhotoController::class, 'remove'])
        ->name('employees.removePhoto');

    Route::get('employees/{employee}/records/pre-requisite', [RecordController::class, 'preRequisite'])->name('employees.records.preRequisite');
    Route::apiResource('employees.records', RecordController::class);

    Route::apiResource('employees.incharges', InchargeController::class)->only(['index']);

    Route::get('employees/{employee}/work-shifts/pre-requisite', [EmployeeWorkShiftController::class, 'preRequisite'])->name('employees.work-shifts.preRequisite');
    Route::apiResource('employees.work-shifts', EmployeeWorkShiftController::class);

    Route::get('employees/{employee}/qualifications/pre-requisite', [QualificationController::class, 'preRequisite'])->name('employees.qualifications.preRequisite');
    Route::post('employees/{employee}/qualifications/{qualification}/action', [QualificationActionController::class, 'action']);
    Route::apiResource('employees.qualifications', QualificationController::class);

    Route::get('employees/{employee}/dialogues/pre-requisite', [DialogueController::class, 'preRequisite'])->middleware('permission:employee:dialogue')->name('employees.dialogues.preRequisite');
    Route::apiResource('employees.dialogues', DialogueController::class)->middleware('permission:employee:dialogue');

    Route::get('employees/{employee}/accounts/pre-requisite', [AccountController::class, 'preRequisite'])->name('employees.accounts.preRequisite');
    Route::post('employees/{employee}/accounts/{account}/action', [AccountActionController::class, 'action']);
    Route::post('employees/{employee}/accounts/{account}/make-primary', [AccountActionController::class, 'makePrimary'])->name('employees.accounts.makePrimary');
    Route::apiResource('employees.accounts', AccountController::class)->names('employees.accounts');

    Route::get('employees/{employee}/documents/pre-requisite', [DocumentController::class, 'preRequisite'])->name('employees.documents.preRequisite');
    Route::post('employees/{employee}/documents/{document}/action', [DocumentActionController::class, 'action']);
    Route::apiResource('employees.documents', DocumentController::class);

    Route::get('employees/{employee}/experiences/pre-requisite', [ExperienceController::class, 'preRequisite'])->name('employees.experiences.preRequisite');
    Route::post('employees/{employee}/experiences/{experience}/action', [ExperienceActionController::class, 'action']);
    Route::apiResource('employees.experiences', ExperienceController::class);

    Route::post('employees/{employee}/tags', [EmployeeActionController::class, 'updateTags'])->name('employees.tags');

    Route::get('employees/pre-requisite', [EmployeeController::class, 'preRequisite'])->name('employees.preRequisite');
    Route::get('employees/list', [EmployeeController::class, 'list'])->name('employees.list');
    Route::post('employees/import', EmployeeImportController::class)->middleware('permission:employees:create')->name('employees.import');

    Route::get('employees/{employee}/edit-requests', [ProfileEditRequestController::class, 'index'])->name('employees.editRequests.index');
    Route::post('employees/{employee}/edit-requests', [ProfileEditRequestController::class, 'store'])->name('employees.editRequests.store');
    Route::get('employees/{employee}/edit-requests/{uuid}', [ProfileEditRequestController::class, 'show'])->name('employees.editRequests.show');

    Route::post('employees/tags', [EmployeeActionController::class, 'updateBulkTags'])->name('employees.updateBulkTags');
    Route::post('employees/groups', [EmployeeActionController::class, 'updateBulkGroups'])->name('employees.updateBulkGroups');

    Route::apiResource('employees', EmployeeController::class);
});
