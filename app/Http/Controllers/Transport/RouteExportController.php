<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Services\Transport\RouteListService;
use Illuminate\Http\Request;

class RouteExportController extends Controller
{
    public function __invoke(Request $request, RouteListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
