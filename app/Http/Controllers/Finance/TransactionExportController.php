<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\TransactionListService;
use Illuminate\Http\Request;

class TransactionExportController extends Controller
{
    public function __invoke(Request $request, TransactionListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
