<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student\Student;
use App\Services\Student\DocumentListService;
use Illuminate\Http\Request;

class DocumentExportController extends Controller
{
    public function __invoke(Request $request, string $student, DocumentListService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        $list = $service->list($request, $student);

        return $service->export($list);
    }
}
