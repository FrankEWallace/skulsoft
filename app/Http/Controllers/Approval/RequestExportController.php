<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Controller;
use App\Services\Approval\RequestListService;
use Illuminate\Http\Request;

class RequestExportController extends Controller
{
    public function __invoke(Request $request, RequestListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
