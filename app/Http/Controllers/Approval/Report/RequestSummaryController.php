<?php

namespace App\Http\Controllers\Approval\Report;

use App\Http\Controllers\Controller;
use App\Services\Approval\Report\RequestSummaryListService;
use App\Services\Approval\Report\RequestSummaryService;
use Illuminate\Http\Request;

class RequestSummaryController extends Controller
{
    public function preRequisite(Request $request, RequestSummaryService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, RequestSummaryListService $service)
    {
        return $service->paginate($request);
    }
}
