<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StockPurchaseRequest;
use App\Http\Resources\Inventory\StockPurchaseResource;
use App\Models\Inventory\StockPurchase;
use App\Services\Inventory\StockPurchaseListService;
use App\Services\Inventory\StockPurchaseService;
use Illuminate\Http\Request;

class StockPurchaseController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, StockPurchaseService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, StockPurchaseListService $service)
    {
        $this->authorize('viewAny', StockPurchase::class);

        return $service->paginate($request);
    }

    public function store(StockPurchaseRequest $request, StockPurchaseService $service)
    {
        $this->authorize('create', StockPurchase::class);

        $stockPurchase = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('inventory.stock_purchase.stock_purchase')]),
            'stock_purchase' => StockPurchaseResource::make($stockPurchase),
        ]);
    }

    public function show(string $stockPurchase, StockPurchaseService $service)
    {
        $stockPurchase = StockPurchase::findByUuidOrFail($stockPurchase);

        $this->authorize('view', $stockPurchase);

        $stockPurchase->load(['inventory', 'place' => fn ($q) => $q->withFloorAndBlock(), 'vendor', 'items.item.category', 'media']);

        return StockPurchaseResource::make($stockPurchase);
    }

    public function update(StockPurchaseRequest $request, string $stockPurchase, StockPurchaseService $service)
    {
        $stockPurchase = StockPurchase::findByUuidOrFail($stockPurchase);

        $this->authorize('update', $stockPurchase);

        $service->update($request, $stockPurchase);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('inventory.stock_purchase.stock_purchase')]),
        ]);
    }

    public function destroy(string $stockPurchase, StockPurchaseService $service)
    {
        $stockPurchase = StockPurchase::findByUuidOrFail($stockPurchase);

        $this->authorize('delete', $stockPurchase);

        $service->deletable($stockPurchase);

        $service->delete($stockPurchase);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('inventory.stock_purchase.stock_purchase')]),
        ]);
    }

    public function downloadMedia(string $stockPurchase, string $uuid, StockPurchaseService $service)
    {
        $stockPurchase = StockPurchase::findByUuidOrFail($stockPurchase);

        $this->authorize('view', $stockPurchase);

        return $stockPurchase->downloadMedia($uuid);
    }
}
