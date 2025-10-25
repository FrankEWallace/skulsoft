<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\FeeStructureListService;
use Illuminate\Http\Request;

class FeeStructureExportController extends Controller
{
    public function __invoke(Request $request, FeeStructureListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
