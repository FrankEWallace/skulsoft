<?php

namespace App\Http\Controllers\Exam\Report;

use App\Http\Controllers\Controller;
use App\Services\Exam\Report\ExamSummaryService;
use Illuminate\Http\Request;

class ExamSummaryController extends Controller
{
    public function preRequisite(Request $request, ExamSummaryService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetchReport(Request $request, ExamSummaryService $service)
    {
        return $service->fetchReport($request);
    }
}
