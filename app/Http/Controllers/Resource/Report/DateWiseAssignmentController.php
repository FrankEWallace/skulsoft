<?php

namespace App\Http\Controllers\Resource\Report;

use App\Http\Controllers\Controller;
use App\Services\Resource\Report\DateWiseAssignmentService;
use Illuminate\Http\Request;

class DateWiseAssignmentController extends Controller
{
    public function preRequisite(Request $request, DateWiseAssignmentService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, DateWiseAssignmentService $service)
    {
        return $service->generate($request);
    }

    public function export(Request $request, DateWiseAssignmentService $service)
    {
        return $service->generate($request);
    }
}
