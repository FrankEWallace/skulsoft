<?php

namespace App\Http\Controllers\Transport\Report;

use App\Http\Controllers\Controller;
use App\Services\Transport\Report\RouteWiseStudentListService;
use App\Services\Transport\Report\RouteWiseStudentService;
use Illuminate\Http\Request;

class RouteWiseStudentController extends Controller
{
    public function preRequisite(Request $request, RouteWiseStudentService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, RouteWiseStudentListService $service)
    {
        return $service->paginate($request);
    }
}
