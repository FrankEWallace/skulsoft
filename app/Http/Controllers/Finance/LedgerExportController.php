<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\LedgerListService;
use Illuminate\Http\Request;

class LedgerExportController extends Controller
{
    public function __invoke(Request $request, LedgerListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
