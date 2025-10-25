<?php

use App\Http\Controllers\Academic\BatchActionController;
use App\Http\Controllers\Academic\BatchController;
use App\Http\Controllers\Academic\BatchImportController;
use App\Http\Controllers\Academic\BatchInchargeController;
use App\Http\Controllers\Academic\BatchListController;
use App\Http\Controllers\Academic\BookListController;
use App\Http\Controllers\Academic\CertificateController;
use App\Http\Controllers\Academic\CertificateTemplateController;
use App\Http\Controllers\Academic\ClassTimingController;
use App\Http\Controllers\Academic\CourseActionController;
use App\Http\Controllers\Academic\CourseController;
use App\Http\Controllers\Academic\CourseImportController;
use App\Http\Controllers\Academic\CourseInchargeController;
use App\Http\Controllers\Academic\DepartmentActionController;
use App\Http\Controllers\Academic\DepartmentController;
use App\Http\Controllers\Academic\DepartmentInchargeController;
use App\Http\Controllers\Academic\DivisionActionController;
use App\Http\Controllers\Academic\DivisionController;
use App\Http\Controllers\Academic\DivisionInchargeController;
use App\Http\Controllers\Academic\EnrollmentSeatController;
use App\Http\Controllers\Academic\IdCardController;
use App\Http\Controllers\Academic\IdCardTemplateController;
use App\Http\Controllers\Academic\PeriodActionController;
use App\Http\Controllers\Academic\PeriodController;
use App\Http\Controllers\Academic\ProgramActionController;
use App\Http\Controllers\Academic\ProgramController;
use App\Http\Controllers\Academic\ProgramInchargeController;
use App\Http\Controllers\Academic\ProgramTypeController;
use App\Http\Controllers\Academic\SessionController;
use App\Http\Controllers\Academic\SubjectActionController;
use App\Http\Controllers\Academic\SubjectController;
use App\Http\Controllers\Academic\SubjectInchargeController;
use App\Http\Controllers\Academic\SubjectInchargeImportController;
use App\Http\Controllers\Academic\SubjectRecordController;
use App\Http\Controllers\Academic\TimetableAllocationController;
use App\Http\Controllers\Academic\TimetableController;
use Illuminate\Support\Facades\Route;

Route::prefix('academic')->name('academic.')->group(function () {
    Route::middleware('permission:academic-department:manage')->group(function () {
        Route::get('departments/pre-requisite', [DepartmentController::class, 'preRequisite'])->name('departments.preRequisite');

        Route::post('departments/reorder', [DepartmentActionController::class, 'reorder'])->name('departments.reorder');

        Route::get('department-incharges/pre-requisite', [DepartmentInchargeController::class, 'preRequisite'])->name('department-incharges.preRequisite');

        Route::apiResource('department-incharges', DepartmentInchargeController::class);

        Route::apiResource('departments', DepartmentController::class);
    });

    Route::middleware('permission:program:manage')->group(function () {
        Route::get('program-types/pre-requisite', [ProgramTypeController::class, 'preRequisite'])->name('program-types.preRequisite');

        Route::apiResource('program-types', ProgramTypeController::class);

        Route::get('programs/pre-requisite', [ProgramController::class, 'preRequisite'])->name('programs.preRequisite');

        Route::post('programs/reorder', [ProgramActionController::class, 'reorder'])->name('programs.reorder');

        Route::get('program-incharges/pre-requisite', [ProgramInchargeController::class, 'preRequisite'])->name('program-incharges.preRequisite');

        Route::apiResource('program-incharges', ProgramInchargeController::class);

        Route::apiResource('programs', ProgramController::class);
    });

    Route::middleware('permission:session:manage')->group(function () {
        Route::get('sessions/pre-requisite', [SessionController::class, 'preRequisite'])->name('sessions.preRequisite');

        Route::apiResource('sessions', SessionController::class);
    });

    Route::get('periods/pre-requisite', [PeriodController::class, 'preRequisite'])->name('periods.preRequisite');

    Route::post('periods/{period}/select', [PeriodActionController::class, 'select'])->name('periods.select')->middleware('permission:period:change');
    Route::post('periods/{period}/default', [PeriodActionController::class, 'default'])->name('periods.default')->middleware('permission:period:update');
    Route::post('periods/{period}/import', [PeriodActionController::class, 'import'])->name('periods.import')->middleware('permission:period:create');

    Route::apiResource('periods', PeriodController::class);

    Route::get('divisions/pre-requisite', [DivisionController::class, 'preRequisite'])->name('divisions.preRequisite');

    Route::post('divisions/reorder', [DivisionActionController::class, 'reorder'])->name('divisions.reorder');

    Route::post('divisions/{division}/config', [DivisionActionController::class, 'updateConfig'])->name('divisions.updateConfig');
    Route::post('divisions/{division}/period', [DivisionActionController::class, 'updateCurrentPeriod'])->name('divisions.updateCurrentPeriod');
    Route::apiResource('divisions', DivisionController::class);

    Route::get('division-incharges/pre-requisite', [DivisionInchargeController::class, 'preRequisite'])->name('division-incharges.preRequisite');

    Route::apiResource('division-incharges', DivisionInchargeController::class);

    Route::get('courses/pre-requisite', [CourseController::class, 'preRequisite'])->name('courses.preRequisite');

    Route::post('courses/{course}/batches', [CourseActionController::class, 'addBatches'])->name('courses.addBatches');
    Route::post('courses/reorder', [CourseActionController::class, 'reorder'])->name('courses.reorder');
    Route::post('courses/reorder-batch', [CourseActionController::class, 'reorderBatch'])->name('courses.reorderBatch');
    Route::post('courses/{course}/enrollment-seat', [CourseActionController::class, 'updateEnrollmentSeat'])->name('courses.updateEnrollmentSeat');

    Route::post('courses/{course}/config', [CourseActionController::class, 'updateConfig'])->name('courses.updateConfig');
    Route::post('courses/{course}/period', [CourseActionController::class, 'updateCurrentPeriod'])->name('courses.updateCurrentPeriod');
    Route::post('courses/import', CourseImportController::class)->middleware('permission:course:create');
    Route::apiResource('courses', CourseController::class);

    Route::get('course-incharges/pre-requisite', [CourseInchargeController::class, 'preRequisite'])->name('course-incharges.preRequisite');

    Route::apiResource('course-incharges', CourseInchargeController::class);

    Route::get('enrollment-seats/pre-requisite', [EnrollmentSeatController::class, 'preRequisite'])->name('enrollment-seats.preRequisite');

    Route::apiResource('enrollment-seats', EnrollmentSeatController::class);

    Route::get('batches/pre-requisite', [BatchController::class, 'preRequisite'])->name('batches.preRequisite');

    Route::get('batches/{batch}/subjects', [BatchListController::class, 'subjects'])->name('batches.subjects');

    Route::post('batches/{batch}/config', [BatchActionController::class, 'updateConfig'])->name('batches.updateConfig');
    Route::post('batches/{batch}/period', [BatchActionController::class, 'updateCurrentPeriod'])->name('batches.updateCurrentPeriod');
    Route::post('batches/import', BatchImportController::class)->middleware('permission:batch:create');
    Route::apiResource('batches', BatchController::class);

    Route::get('batch-incharges/pre-requisite', [BatchInchargeController::class, 'preRequisite'])->name('batch-incharges.preRequisite');

    Route::apiResource('batch-incharges', BatchInchargeController::class);

    Route::get('subjects/pre-requisite', [SubjectController::class, 'preRequisite'])->name('subjects.preRequisite');

    Route::post('subjects/reorder', [SubjectActionController::class, 'reorder'])->name('subjects.reorder');

    Route::apiResource('subjects.records', SubjectRecordController::class);

    Route::post('subjects/{subject}/fee', [SubjectActionController::class, 'updateFee'])->name('subjects.updateFee');

    Route::apiResource('subjects', SubjectController::class);

    Route::get('book-lists/pre-requisite', [BookListController::class, 'preRequisite'])->name('book-lists.preRequisite');

    Route::apiResource('book-lists', BookListController::class);

    Route::get('subject-incharges/pre-requisite', [SubjectInchargeController::class, 'preRequisite'])->name('subject-incharges.preRequisite');

    Route::post('subject-incharges/import', SubjectInchargeImportController::class)->middleware('permission:subject-incharge:create');
    Route::apiResource('subject-incharges', SubjectInchargeController::class);

    Route::get('certificate-templates/pre-requisite', [CertificateTemplateController::class, 'preRequisite'])->name('certificateTemplates.preRequisite');

    Route::apiResource('certificate-templates', CertificateTemplateController::class);

    Route::get('certificates/pre-requisite', [CertificateController::class, 'preRequisite'])->name('certificates.preRequisite');

    Route::apiResource('certificates', CertificateController::class);

    Route::get('id-card-templates/pre-requisite', [IdCardTemplateController::class, 'preRequisite'])->name('idCardTemplates.preRequisite');

    Route::apiResource('id-card-templates', IdCardTemplateController::class);

    Route::get('id-cards/pre-requisite', [IdCardController::class, 'preRequisite'])->name('idCards.preRequisite');
    Route::get('id-cards', [IdCardController::class, 'print'])->name('idCards.print');

    Route::get('class-timings/pre-requisite', [ClassTimingController::class, 'preRequisite'])->name('class-timings.preRequisite');

    Route::apiResource('class-timings', ClassTimingController::class);

    Route::get('timetables/pre-requisite', [TimetableController::class, 'preRequisite'])->name('timetables.preRequisite');

    Route::get('timetables/{timetable}/allocation/pre-requisite', [TimetableAllocationController::class, 'preRequisite'])->middleware('permission:timetable:allocate')->name('timetables.allocation.preRequisite');
    Route::post('timetables/{timetable}/allocation', [TimetableAllocationController::class, 'allocation'])->middleware('permission:timetable:allocate')->name('timetables.allocation');

    Route::apiResource('timetables', TimetableController::class);
});
