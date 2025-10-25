<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Student\StudentListService;
use Illuminate\Http\Request;

class StudentExportController extends Controller
{
    public function __invoke(Request $request, StudentListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
