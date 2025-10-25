<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\InventoryRequest;
use App\Http\Resources\Inventory\InventoryResource;
use App\Models\Inventory\Inventory;
use App\Services\Inventory\InventoryListService;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, InventoryService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, InventoryListService $service)
    {
        return $service->paginate($request);
    }

    public function store(InventoryRequest $request, InventoryService $service)
    {
        $inventory = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('inventory.inventory')]),
            'stock_item' => InventoryResource::make($inventory),
        ]);
    }

    public function show(string $inventory, InventoryService $service)
    {
        $inventory = Inventory::findByUuidOrFail($inventory);

        $inventory->load(['incharges.employee' => fn ($q) => $q->detail()]);

        return InventoryResource::make($inventory);
    }

    public function update(InventoryRequest $request, string $inventory, InventoryService $service)
    {
        $inventory = Inventory::findByUuidOrFail($inventory);

        $service->update($request, $inventory);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('inventory.inventory')]),
        ]);
    }

    public function destroy(string $inventory, InventoryService $service)
    {
        $inventory = Inventory::findByUuidOrFail($inventory);

        $service->deletable($inventory);

        $inventory->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('inventory.inventory')]),
        ]);
    }
}
