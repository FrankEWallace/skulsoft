<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student\Student;
use App\Services\Student\RecordListService;
use Illuminate\Http\Request;

class RecordExportController extends Controller
{
    public function __invoke(Request $request, string $student, RecordListService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $list = $service->list($request, $student);

        return $service->export($list);
    }
}
