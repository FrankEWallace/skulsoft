<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Services\Transport\StoppageListService;
use Illuminate\Http\Request;

class StoppageExportController extends Controller
{
    public function __invoke(Request $request, StoppageListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
