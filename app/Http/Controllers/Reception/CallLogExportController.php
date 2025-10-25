<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Services\Reception\CallLogListService;
use Illuminate\Http\Request;

class CallLogExportController extends Controller
{
    public function __invoke(Request $request, CallLogListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
