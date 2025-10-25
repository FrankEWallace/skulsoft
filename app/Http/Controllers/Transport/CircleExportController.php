<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Services\Transport\CircleListService;
use Illuminate\Http\Request;

class CircleExportController extends Controller
{
    public function __invoke(Request $request, CircleListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
