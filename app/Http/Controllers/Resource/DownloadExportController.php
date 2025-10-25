<?php

namespace App\Http\Controllers\Resource;

use App\Http\Controllers\Controller;
use App\Services\Resource\DownloadListService;
use Illuminate\Http\Request;

class DownloadExportController extends Controller
{
    public function __invoke(Request $request, DownloadListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
