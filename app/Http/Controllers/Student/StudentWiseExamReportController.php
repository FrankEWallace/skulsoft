<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student\Student;
use App\Services\Student\StudentWiseExamReportService;
use Illuminate\Http\Request;

class StudentWiseExamReportController extends Controller
{
    public function preRequisite(Request $request, StudentWiseExamReportService $service) {}

    public function fetch(Request $request, string $student, StudentWiseExamReportService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        return response()->ok($service->fetch($request, $student));
    }
}
