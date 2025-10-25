<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student\Student;
use App\Services\Student\StudentWiseAttendanceService;
use Illuminate\Http\Request;

class StudentWiseAttendanceController extends Controller
{
    public function preRequisite(Request $request, StudentWiseAttendanceService $service) {}

    public function fetch(Request $request, string $student, StudentWiseAttendanceService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        return response()->ok($service->fetch($request, $student));
    }
}
