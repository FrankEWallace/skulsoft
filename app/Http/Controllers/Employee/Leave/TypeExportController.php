<?php

namespace App\Http\Controllers\Employee\Leave;

use App\Http\Controllers\Controller;
use App\Services\Employee\Leave\TypeListService as LeaveTypeListService;
use Illuminate\Http\Request;

class TypeExportController extends Controller
{
    public function __invoke(Request $request, LeaveTypeListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
