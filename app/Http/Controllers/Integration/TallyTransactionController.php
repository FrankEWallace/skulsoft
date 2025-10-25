<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Services\Integration\TallyTransactionService;
use Illuminate\Http\Request;

class TallyTransactionController extends Controller
{
    public function __invoke(Request $request, TallyTransactionService $tallyService)
    {
        return $tallyService->getTransactions($request);
    }
}
