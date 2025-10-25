<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student\Student;
use App\Services\Student\StudentWiseSubjectService;
use Illuminate\Http\Request;

class StudentWiseSubjectController extends Controller
{
    public function preRequisite(Request $request, StudentWiseSubjectService $service) {}

    public function fetch(Request $request, string $student, StudentWiseSubjectService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        return response()->ok($service->fetch($request, $student));
    }
}
