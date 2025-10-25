<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Site\MenuListService;
use Illuminate\Http\Request;

class MenuExportController extends Controller
{
    public function __invoke(Request $request, MenuListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
