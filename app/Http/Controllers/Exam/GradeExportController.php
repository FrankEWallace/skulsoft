<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Services\Exam\GradeListService;
use Illuminate\Http\Request;

class GradeExportController extends Controller
{
    public function __invoke(Request $request, GradeListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
