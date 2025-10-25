<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\VendorListService;
use Illuminate\Http\Request;

class VendorExportController extends Controller
{
    public function __invoke(Request $request, VendorListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
