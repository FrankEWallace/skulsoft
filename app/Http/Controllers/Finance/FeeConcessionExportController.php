<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\FeeConcessionListService;
use Illuminate\Http\Request;

class FeeConcessionExportController extends Controller
{
    public function __invoke(Request $request, FeeConcessionListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
