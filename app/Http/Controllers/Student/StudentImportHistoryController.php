<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Student\StudentImportHistoryService;
use Illuminate\Http\Request;

class StudentImportHistoryController extends Controller
{
    public function index(Request $request, StudentImportHistoryService $service)
    {
        return $service->fetch($request);
    }

    public function destroy(Request $request, string $uuid, StudentImportHistoryService $service)
    {
        $service->delete($request, $uuid);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('student.student')]),
        ]);
    }
}
