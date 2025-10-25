<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\InchargeListService;
use Illuminate\Http\Request;

class InchargeExportController extends Controller
{
    public function __invoke(Request $request, InchargeListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
