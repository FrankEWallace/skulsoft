<?php

namespace App\Http\Controllers\Asset\Building;

use App\Http\Controllers\Controller;
use App\Services\Asset\Building\BlockListService;
use Illuminate\Http\Request;

class BlockExportController extends Controller
{
    public function __invoke(Request $request, BlockListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
