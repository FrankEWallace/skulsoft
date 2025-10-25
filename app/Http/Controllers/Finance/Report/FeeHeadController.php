<?php

namespace App\Http\Controllers\Finance\Report;

use App\Http\Controllers\Controller;
use App\Services\Finance\Report\FeeHeadListService;
use App\Services\Finance\Report\FeeHeadService;
use Illuminate\Http\Request;

class FeeHeadController extends Controller
{
    public function preRequisite(Request $request, FeeHeadService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, FeeHeadListService $service)
    {
        return $service->paginate($request);
    }
}
