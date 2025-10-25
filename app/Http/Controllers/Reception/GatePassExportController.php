<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Services\Reception\GatePassListService;
use Illuminate\Http\Request;

class GatePassExportController extends Controller
{
    public function __invoke(Request $request, GatePassListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
