<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Services\Transport\FeeListService;
use Illuminate\Http\Request;

class FeeExportController extends Controller
{
    public function __invoke(Request $request, FeeListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
