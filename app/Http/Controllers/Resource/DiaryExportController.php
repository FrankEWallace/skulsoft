<?php

namespace App\Http\Controllers\Resource;

use App\Http\Controllers\Controller;
use App\Services\Resource\DiaryListService;
use Illuminate\Http\Request;

class DiaryExportController extends Controller
{
    public function __invoke(Request $request, DiaryListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
