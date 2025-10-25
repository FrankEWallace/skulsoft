<?php

namespace App\Http\Controllers\Exam\Report;

use App\Http\Controllers\Controller;
use App\Services\Exam\Report\MarkSummaryService;
use Illuminate\Http\Request;

class MarkSummaryController extends Controller
{
    public function preRequisite(Request $request, MarkSummaryService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetchReport(Request $request, MarkSummaryService $service)
    {
        return $service->fetchReport($request);
    }
}
