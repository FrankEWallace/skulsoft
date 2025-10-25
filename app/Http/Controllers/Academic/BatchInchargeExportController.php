<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Academic\BatchInchargeListService;
use Illuminate\Http\Request;

class BatchInchargeExportController extends Controller
{
    public function __invoke(Request $request, BatchInchargeListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
