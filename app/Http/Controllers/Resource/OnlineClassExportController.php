<?php

namespace App\Http\Controllers\Resource;

use App\Http\Controllers\Controller;
use App\Services\Resource\OnlineClassListService;
use Illuminate\Http\Request;

class OnlineClassExportController extends Controller
{
    public function __invoke(Request $request, OnlineClassListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
