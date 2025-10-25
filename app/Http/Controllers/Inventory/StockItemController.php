<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StockItemRequest;
use App\Http\Resources\Inventory\StockItemResource;
use App\Models\Inventory\StockBalance;
use App\Models\Inventory\StockItem;
use App\Services\Inventory\StockItemListService;
use App\Services\Inventory\StockItemService;
use Illuminate\Http\Request;

class StockItemController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, StockItemService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, StockItemListService $service)
    {
        $this->authorize('viewAny', StockItem::class);

        return $service->paginate($request);
    }

    public function store(StockItemRequest $request, StockItemService $service)
    {
        $this->authorize('create', StockItem::class);

        $stockItem = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('inventory.stock_item.stock_item')]),
            'stock_item' => StockItemResource::make($stockItem),
        ]);
    }

    public function show(Request $request, string $stockItem, StockItemService $service)
    {
        $stockItem = StockItem::findByUuidOrFail($stockItem);

        $this->authorize('view', $stockItem);

        $stockItem->load('category');

        $request->merge([
            'show_details' => true,
        ]);

        if ($stockItem->is_quantity_editable) {
            $stockBalance = StockBalance::query()
                ->with('place')
                ->whereStockItemId($stockItem->id)
                ->first();

            $stockItem->place = $stockBalance ? [
                'uuid' => $stockBalance?->place->uuid,
            ] : null;
            $stockItem->quantity = $stockBalance?->current_quantity ?? 0;
        }

        return StockItemResource::make($stockItem);
    }

    public function update(StockItemRequest $request, string $stockItem, StockItemService $service)
    {
        $stockItem = StockItem::findByUuidOrFail($stockItem);

        $this->authorize('update', $stockItem);

        $service->update($request, $stockItem);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('inventory.stock_item.stock_item')]),
        ]);
    }

    public function destroy(string $stockItem, StockItemService $service)
    {
        $stockItem = StockItem::findByUuidOrFail($stockItem);

        $this->authorize('delete', $stockItem);

        $service->deletable($stockItem);

        $stockItem->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('inventory.stock_item.stock_item')]),
        ]);
    }
}
