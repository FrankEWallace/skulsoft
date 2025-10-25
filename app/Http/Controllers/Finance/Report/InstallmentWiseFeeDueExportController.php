<?php

namespace App\Http\Controllers\Finance\Report;

use App\Http\Controllers\Controller;
use App\Services\Finance\Report\InstallmentWiseFeeDueListService;
use Illuminate\Http\Request;

class InstallmentWiseFeeDueExportController extends Controller
{
    public function __invoke(Request $request, InstallmentWiseFeeDueListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
