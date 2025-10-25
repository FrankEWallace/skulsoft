<?php

namespace App\Http\Controllers\Finance\Report;

use App\Http\Controllers\Controller;
use App\Services\Finance\Report\FeeDueListService;
use App\Services\Finance\Report\FeeDueService;
use Illuminate\Http\Request;

class FeeDueController extends Controller
{
    public function preRequisite(Request $request, FeeDueService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, FeeDueListService $service)
    {
        return $service->paginate($request);
    }
}
