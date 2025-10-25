<?php

namespace App\Http\Controllers\Utility;

use App\Http\Controllers\Controller;
use App\Services\Utility\ActivityLogListService;
use Illuminate\Http\Request;

class ActivityLogExportController extends Controller
{
    public function __invoke(Request $request, ActivityLogListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
