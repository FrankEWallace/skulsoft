<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mess\MenuItemRequest;
use App\Http\Resources\Mess\MenuItemResource;
use App\Models\Mess\MenuItem;
use App\Services\Mess\MenuItemListService;
use App\Services\Mess\MenuItemService;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, MenuItemService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, MenuItemListService $service)
    {
        return $service->paginate($request);
    }

    public function store(MenuItemRequest $request, MenuItemService $service)
    {
        $menuItem = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('mess.menu.item')]),
            'menu_item' => MenuItemResource::make($menuItem),
        ]);
    }

    public function show(MenuItem $menuItem, MenuItemService $service)
    {
        return MenuItemResource::make($menuItem);
    }

    public function update(MenuItemRequest $request, MenuItem $menuItem, MenuItemService $service)
    {
        $service->update($request, $menuItem);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('mess.menu.item')]),
        ]);
    }

    public function destroy(MenuItem $menuItem, MenuItemService $service)
    {
        $service->deletable($menuItem);

        $menuItem->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('mess.menu.item')]),
        ]);
    }
}
