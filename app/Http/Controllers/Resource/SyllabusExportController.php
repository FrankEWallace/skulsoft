<?php

namespace App\Http\Controllers\Resource;

use App\Http\Controllers\Controller;
use App\Services\Resource\SyllabusListService;
use Illuminate\Http\Request;

class SyllabusExportController extends Controller
{
    public function __invoke(Request $request, SyllabusListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
