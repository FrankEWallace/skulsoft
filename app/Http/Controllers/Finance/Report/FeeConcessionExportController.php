<?php

namespace App\Http\Controllers\Finance\Report;

use App\Http\Controllers\Controller;
use App\Services\Finance\Report\FeeConcessionListService;
use Illuminate\Http\Request;

class FeeConcessionExportController extends Controller
{
    public function __invoke(Request $request, FeeConcessionListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
