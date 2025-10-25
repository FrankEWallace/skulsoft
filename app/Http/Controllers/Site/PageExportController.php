<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Site\PageListService;
use Illuminate\Http\Request;

class PageExportController extends Controller
{
    public function __invoke(Request $request, PageListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
