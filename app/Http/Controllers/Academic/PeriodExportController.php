<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Academic\PeriodListService;
use Illuminate\Http\Request;

class PeriodExportController extends Controller
{
    public function __invoke(Request $request, PeriodListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
