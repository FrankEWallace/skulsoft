<?php

namespace App\Http\Controllers\Employee\Leave;

use App\Http\Controllers\Controller;
use App\Services\Employee\Leave\RequestListService as LeaveRequestListService;
use Illuminate\Http\Request;

class RequestExportController extends Controller
{
    public function __invoke(Request $request, LeaveRequestListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
