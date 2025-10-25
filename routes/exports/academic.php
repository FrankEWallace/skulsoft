<?php

use App\Http\Controllers\Academic\BatchExportController;
use App\Http\Controllers\Academic\BatchInchargeExportController;
use App\Http\Controllers\Academic\BookListExportController;
use App\Http\Controllers\Academic\CertificateController;
use App\Http\Controllers\Academic\CertificateExportController;
use App\Http\Controllers\Academic\CertificateTemplateController;
use App\Http\Controllers\Academic\CertificateTemplateExportController;
use App\Http\Controllers\Academic\ClassTimingExportController;
use App\Http\Controllers\Academic\CourseExportController;
use App\Http\Controllers\Academic\CourseInchargeExportController;
use App\Http\Controllers\Academic\DepartmentExportController;
use App\Http\Controllers\Academic\DepartmentInchargeExportController;
use App\Http\Controllers\Academic\DivisionExportController;
use App\Http\Controllers\Academic\DivisionInchargeExportController;
use App\Http\Controllers\Academic\EnrollmentSeatExportController;
use App\Http\Controllers\Academic\IdCardController;
use App\Http\Controllers\Academic\IdCardTemplateController;
use App\Http\Controllers\Academic\IdCardTemplateExportController;
use App\Http\Controllers\Academic\PeriodExportController;
use App\Http\Controllers\Academic\ProgramExportController;
use App\Http\Controllers\Academic\ProgramInchargeExportController;
use App\Http\Controllers\Academic\ProgramTypeExportController;
use App\Http\Controllers\Academic\SessionExportController;
use App\Http\Controllers\Academic\SubjectExportController;
use App\Http\Controllers\Academic\SubjectInchargeExportController;
use App\Http\Controllers\Academic\TimetableController;
use App\Http\Controllers\Academic\TimetableExportController;
use Illuminate\Support\Facades\Route;

Route::get('academic/departments/export', DepartmentExportController::class)->middleware('permission:academic-department:manage')->name('academic.departments.export');

Route::get('academic/department-incharges/export', DepartmentInchargeExportController::class)->middleware('permission:academic-department:manage')->name('academic.department-incharges.export');

Route::get('academic/program-types/export', ProgramTypeExportController::class)->middleware('permission:program:manage')->name('academic.program-types.export');

Route::get('academic/programs/export', ProgramExportController::class)->middleware('permission:program:manage')->name('academic.programs.export');

Route::get('academic/program-incharges/export', ProgramInchargeExportController::class)->middleware('permission:program:manage')->name('academic.program-incharges.export');

Route::get('academic/sessions/export', SessionExportController::class)->middleware('permission:session:manage')->name('academic.sessions.export');

Route::get('academic/periods/export', PeriodExportController::class)->middleware('permission:period:export')->name('academic.periods.export');

Route::get('academic/division-incharges/export', DivisionInchargeExportController::class)->middleware('permission:division:export')->name('academic.division-incharges.export');

Route::get('academic/divisions/export', DivisionExportController::class)->middleware('permission:division:export')->name('academic.divisions.export');

Route::get('academic/course-incharges/export', CourseInchargeExportController::class)->middleware('permission:course:export')->name('academic.course-incharges.export');

Route::get('academic/courses/export', CourseExportController::class)->middleware('permission:course:export')->name('academic.courses.export');

Route::get('academic/enrollment-seats/export', EnrollmentSeatExportController::class)->middleware('permission:course:export')->name('academic.enrollment-seats.export');

Route::get('academic/batches/export', BatchExportController::class)->middleware('permission:batch:export')->name('academic.batches.export');

Route::get('academic/batch-incharges/export', BatchInchargeExportController::class)->middleware('permission:batch:export')->name('academic.batch-incharges.export');

Route::get('academic/subjects/export', SubjectExportController::class)->middleware('permission:subject:export')->name('academic.subjects.export');

Route::get('academic/book-lists/export', BookListExportController::class)->middleware('permission:book-list:manage')->name('academic.book-lists.export');

Route::get('academic/subject-incharges/export', SubjectInchargeExportController::class)->middleware('permission:batch:export')->name('academic.subject-incharges.export');

Route::get('academic/certificate-templates/export', CertificateTemplateExportController::class)->middleware('permission:certificate-template:export')->name('academic.certificateTemplates.export');

Route::get('academic/certificate-templates/{certificate_template}/export', [CertificateTemplateController::class, 'export'])->name('academic.certificate-template.export');

Route::get('academic/certificates/export', CertificateExportController::class)->middleware('permission:certificate:export')->name('academic.certificates.export');

Route::get('academic/certificates/export-all', [CertificateController::class, 'exportAll'])->middleware('permission:certificate:export')->name('academic.certificates.export-all');

Route::get('academic/certificates/{certificate}/export', [CertificateController::class, 'export'])->name('academic.certificate.export');

Route::get('academic/id-card-templates/export', IdCardTemplateExportController::class)->middleware('permission:id-card:manage')->name('academic.idCards.export');

Route::get('academic/id-card-templates/{id_card_template}/export', [IdCardTemplateController::class, 'export'])->name('academic.id-card-template.export');

Route::get('academic/id-cards/print', [IdCardController::class, 'print'])->middleware('permission:id-card:manage')->name('academic.idCards.print');

Route::get('academic/class-timings/export', ClassTimingExportController::class)->middleware('permission:class-timing:export')->name('academic.class-timings.export');

Route::get('academic/timetables/teacher/export', [TimetableController::class, 'exportTeacherTimetable'])->name('academic.timetable.teacher.export');

Route::get('academic/timetables/{timetable}/export', [TimetableController::class, 'export'])->name('academic.timetable.export');

Route::get('academic/timetables/export', TimetableExportController::class)->middleware('permission:timetable:export')->name('academic.timetables.export');
