<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\VendorStatementService;
use Illuminate\Http\Request;

class VendorStatementExportController extends Controller
{
    public function __invoke(Request $request, VendorStatementService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
