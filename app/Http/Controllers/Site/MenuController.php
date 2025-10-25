<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\MenuRequest;
use App\Http\Resources\Site\MenuResource;
use App\Models\Site\Menu;
use App\Services\Site\MenuListService;
use App\Services\Site\MenuService;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:site:manage');
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, MenuService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, MenuListService $service)
    {
        return $service->paginate($request);
    }

    public function store(MenuRequest $request, MenuService $service)
    {
        $menu = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('site.menu.menu')]),
            'menu' => MenuResource::make($menu),
        ]);
    }

    public function show(Menu $menu, MenuService $service)
    {
        $menu->load('page', 'parent');

        return MenuResource::make($menu);
    }

    public function update(MenuRequest $request, Menu $menu, MenuService $service)
    {
        $service->update($request, $menu);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('site.menu.menu')]),
        ]);
    }

    public function destroy(Menu $menu, MenuService $service)
    {
        $service->deletable($menu);

        $menu->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('site.menu.menu')]),
        ]);
    }

    public function downloadMedia(Menu $menu, string $uuid, MenuService $service)
    {
        return $menu->downloadMedia($uuid);
    }
}
