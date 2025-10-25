<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Academic\DepartmentListService;
use Illuminate\Http\Request;

class DepartmentExportController extends Controller
{
    public function __invoke(Request $request, DepartmentListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
