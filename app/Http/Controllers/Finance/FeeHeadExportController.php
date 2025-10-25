<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\FeeHeadListService;
use Illuminate\Http\Request;

class FeeHeadExportController extends Controller
{
    public function __invoke(Request $request, FeeHeadListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
