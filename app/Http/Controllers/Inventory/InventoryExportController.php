<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\InventoryListService;
use Illuminate\Http\Request;

class InventoryExportController extends Controller
{
    public function __invoke(Request $request, InventoryListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
