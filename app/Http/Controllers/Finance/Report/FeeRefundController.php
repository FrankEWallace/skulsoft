<?php

namespace App\Http\Controllers\Finance\Report;

use App\Http\Controllers\Controller;
use App\Services\Finance\Report\FeeRefundListService;
use App\Services\Finance\Report\FeeRefundService;
use Illuminate\Http\Request;

class FeeRefundController extends Controller
{
    public function preRequisite(Request $request, FeeRefundService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, FeeRefundListService $service)
    {
        return $service->paginate($request);
    }
}
