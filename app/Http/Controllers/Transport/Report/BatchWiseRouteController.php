<?php

namespace App\Http\Controllers\Transport\Report;

use App\Http\Controllers\Controller;
use App\Services\Transport\Report\BatchWiseRouteListService;
use App\Services\Transport\Report\BatchWiseRouteService;
use Illuminate\Http\Request;

class BatchWiseRouteController extends Controller
{
    public function preRequisite(Request $request, BatchWiseRouteService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, BatchWiseRouteListService $service)
    {
        return $service->paginate($request);
    }
}
