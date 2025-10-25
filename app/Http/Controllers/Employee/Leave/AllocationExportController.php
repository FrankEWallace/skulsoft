<?php

namespace App\Http\Controllers\Employee\Leave;

use App\Http\Controllers\Controller;
use App\Services\Employee\Leave\AllocationListService as LeaveAllocationListService;
use Illuminate\Http\Request;

class AllocationExportController extends Controller
{
    public function __invoke(Request $request, LeaveAllocationListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
