<?php

namespace App\Http\Controllers\Inventory\Report;

use App\Http\Controllers\Controller;
use App\Services\Inventory\Report\ItemSummaryListService;
use App\Services\Inventory\Report\ItemSummaryService;
use Illuminate\Http\Request;

class ItemSummaryController extends Controller
{
    public function preRequisite(Request $request, ItemSummaryService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, ItemSummaryListService $service)
    {
        return $service->paginate($request);
    }
}
