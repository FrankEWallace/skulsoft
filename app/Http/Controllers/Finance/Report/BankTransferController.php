<?php

namespace App\Http\Controllers\Finance\Report;

use App\Http\Controllers\Controller;
use App\Services\Finance\Report\BankTransferListService;
use App\Services\Finance\Report\BankTransferService;
use Illuminate\Http\Request;

class BankTransferController extends Controller
{
    public function preRequisite(Request $request, BankTransferService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, BankTransferListService $service)
    {
        return $service->paginate($request);
    }
}
