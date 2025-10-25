<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StockAdjustmentRequest;
use App\Http\Resources\Inventory\StockAdjustmentResource;
use App\Models\Inventory\StockAdjustment;
use App\Services\Inventory\StockAdjustmentListService;
use App\Services\Inventory\StockAdjustmentService;
use Illuminate\Http\Request;

class StockAdjustmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, StockAdjustmentService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, StockAdjustmentListService $service)
    {
        $this->authorize('viewAny', StockAdjustment::class);

        return $service->paginate($request);
    }

    public function store(StockAdjustmentRequest $request, StockAdjustmentService $service)
    {
        $this->authorize('create', StockAdjustment::class);

        $stockAdjustment = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('inventory.stock_adjustment.stock_adjustment')]),
            'stock_adjustment' => StockAdjustmentResource::make($stockAdjustment),
        ]);
    }

    public function show(string $stockAdjustment, StockAdjustmentService $service)
    {
        $stockAdjustment = StockAdjustment::findByUuidOrFail($stockAdjustment);

        $this->authorize('view', $stockAdjustment);

        $stockAdjustment->load(['inventory', 'place' => fn ($q) => $q->withFloorAndBlock(), 'items.item.category', 'media']);

        return StockAdjustmentResource::make($stockAdjustment);
    }

    public function update(StockAdjustmentRequest $request, string $stockAdjustment, StockAdjustmentService $service)
    {
        $stockAdjustment = StockAdjustment::findByUuidOrFail($stockAdjustment);

        $this->authorize('update', $stockAdjustment);

        $service->update($request, $stockAdjustment);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('inventory.stock_adjustment.stock_adjustment')]),
        ]);
    }

    public function destroy(string $stockAdjustment, StockAdjustmentService $service)
    {
        $stockAdjustment = StockAdjustment::findByUuidOrFail($stockAdjustment);

        $this->authorize('delete', $stockAdjustment);

        $service->deletable($stockAdjustment);

        $service->delete($stockAdjustment);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('inventory.stock_adjustment.stock_adjustment')]),
        ]);
    }

    public function downloadMedia(string $stockAdjustment, string $uuid, StockAdjustmentService $service)
    {
        $stockAdjustment = StockAdjustment::findByUuidOrFail($stockAdjustment);

        $this->authorize('view', $stockAdjustment);

        return $stockAdjustment->downloadMedia($uuid);
    }
}
