<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Academic\BatchListService;
use Illuminate\Http\Request;

class BatchExportController extends Controller
{
    public function __invoke(Request $request, BatchListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
