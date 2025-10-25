<?php

namespace App\Http\Controllers\Hostel;

use App\Http\Controllers\Controller;
use App\Services\Hostel\FloorListService;
use Illuminate\Http\Request;

class FloorExportController extends Controller
{
    public function __invoke(Request $request, FloorListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
