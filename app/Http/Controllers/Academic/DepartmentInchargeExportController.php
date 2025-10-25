<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Academic\DepartmentInchargeListService;
use Illuminate\Http\Request;

class DepartmentInchargeExportController extends Controller
{
    public function __invoke(Request $request, DepartmentInchargeListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
