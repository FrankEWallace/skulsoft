<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\FeeGroupListService;
use Illuminate\Http\Request;

class FeeGroupExportController extends Controller
{
    public function __invoke(Request $request, FeeGroupListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
