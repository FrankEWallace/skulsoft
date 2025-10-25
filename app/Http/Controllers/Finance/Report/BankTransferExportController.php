<?php

namespace App\Http\Controllers\Finance\Report;

use App\Http\Controllers\Controller;
use App\Services\Finance\Report\BankTransferListService;
use Illuminate\Http\Request;

class BankTransferExportController extends Controller
{
    public function __invoke(Request $request, BankTransferListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
