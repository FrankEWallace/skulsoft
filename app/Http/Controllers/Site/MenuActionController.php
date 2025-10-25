<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Site\MenuActionService;
use Illuminate\Http\Request;

class MenuActionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:site:manage');
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function reorder(Request $request, MenuActionService $service)
    {
        $menu = $service->reorder($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('site.menu.menu')]),
        ]);
    }

    public function reorderSubMenu(Request $request, MenuActionService $service)
    {
        $menu = $service->reorderSubMenu($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('site.menu.menu')]),
        ]);
    }
}
