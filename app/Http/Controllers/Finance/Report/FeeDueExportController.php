<?php

namespace App\Http\Controllers\Finance\Report;

use App\Http\Controllers\Controller;
use App\Services\Finance\Report\FeeDueListService;
use Illuminate\Http\Request;

class FeeDueExportController extends Controller
{
    public function __invoke(Request $request, FeeDueListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
