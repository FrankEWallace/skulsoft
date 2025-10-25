<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Services\Exam\AssessmentListService;
use Illuminate\Http\Request;

class AssessmentExportController extends Controller
{
    public function __invoke(Request $request, AssessmentListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
