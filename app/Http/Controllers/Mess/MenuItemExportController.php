<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Services\Mess\MenuItemListService;
use Illuminate\Http\Request;

class MenuItemExportController extends Controller
{
    public function __invoke(Request $request, MenuItemListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
