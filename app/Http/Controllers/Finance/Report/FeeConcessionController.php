<?php

namespace App\Http\Controllers\Finance\Report;

use App\Http\Controllers\Controller;
use App\Services\Finance\Report\FeeConcessionListService;
use App\Services\Finance\Report\FeeConcessionService;
use Illuminate\Http\Request;

class FeeConcessionController extends Controller
{
    public function preRequisite(Request $request, FeeConcessionService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, FeeConcessionListService $service)
    {
        return $service->paginate($request);
    }
}
