<?php

namespace App\Http\Controllers\Finance\Report;

use App\Http\Controllers\Controller;
use App\Services\Finance\Report\FeeHeadListService;
use Illuminate\Http\Request;

class FeeHeadExportController extends Controller
{
    public function __invoke(Request $request, FeeHeadListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
