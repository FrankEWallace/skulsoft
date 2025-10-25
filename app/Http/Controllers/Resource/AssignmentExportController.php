<?php

namespace App\Http\Controllers\Resource;

use App\Http\Controllers\Controller;
use App\Services\Resource\AssignmentListService;
use Illuminate\Http\Request;

class AssignmentExportController extends Controller
{
    public function __invoke(Request $request, AssignmentListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
