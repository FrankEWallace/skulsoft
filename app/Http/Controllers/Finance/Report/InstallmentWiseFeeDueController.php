<?php

namespace App\Http\Controllers\Finance\Report;

use App\Http\Controllers\Controller;
use App\Services\Finance\Report\InstallmentWiseFeeDueListService;
use App\Services\Finance\Report\InstallmentWiseFeeDueService;
use Illuminate\Http\Request;

class InstallmentWiseFeeDueController extends Controller
{
    public function preRequisite(Request $request, InstallmentWiseFeeDueService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, InstallmentWiseFeeDueListService $service)
    {
        return $service->paginate($request);
    }
}
