<?php

namespace App\Http\Controllers\Asset\Building;

use App\Http\Controllers\Controller;
use App\Services\Asset\Building\FloorListService;
use Illuminate\Http\Request;

class FloorExportController extends Controller
{
    public function __invoke(Request $request, FloorListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
