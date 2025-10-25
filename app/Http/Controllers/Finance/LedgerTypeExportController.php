<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\LedgerTypeListService;
use Illuminate\Http\Request;

class LedgerTypeExportController extends Controller
{
    public function __invoke(Request $request, LedgerTypeListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
