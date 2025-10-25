<?php

namespace App\Http\Controllers\Finance\Report;

use App\Http\Controllers\Controller;
use App\Services\Finance\Report\FeeSummaryListService;
use App\Services\Finance\Report\FeeSummaryService;
use Illuminate\Http\Request;

class FeeSummaryController extends Controller
{
    public function preRequisite(Request $request, FeeSummaryService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, FeeSummaryListService $service)
    {
        return $service->paginate($request);
    }
}
