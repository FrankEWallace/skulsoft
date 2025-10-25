<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student\Student;
use App\Services\Student\GuardianListService;
use Illuminate\Http\Request;

class GuardianExportController extends Controller
{
    public function __invoke(Request $request, string $student, GuardianListService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $list = $service->list($request, $student);

        return $service->export($list);
    }
}
