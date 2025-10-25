<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Services\Reception\VisitorLogListService;
use Illuminate\Http\Request;

class VisitorLogExportController extends Controller
{
    public function __invoke(Request $request, VisitorLogListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
