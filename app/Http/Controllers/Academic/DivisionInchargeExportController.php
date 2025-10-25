<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Academic\DivisionInchargeListService;
use Illuminate\Http\Request;

class DivisionInchargeExportController extends Controller
{
    public function __invoke(Request $request, DivisionInchargeListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
