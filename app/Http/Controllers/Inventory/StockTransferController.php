<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StockTransferRequest;
use App\Http\Resources\Inventory\StockTransferResource;
use App\Models\Inventory\StockTransfer;
use App\Services\Inventory\StockTransferListService;
use App\Services\Inventory\StockTransferService;
use Illuminate\Http\Request;

class StockTransferController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, StockTransferService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, StockTransferListService $service)
    {
        $this->authorize('viewAny', StockTransfer::class);

        return $service->paginate($request);
    }

    public function store(StockTransferRequest $request, StockTransferService $service)
    {
        $this->authorize('create', StockTransfer::class);

        $stockTransfer = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('inventory.stock_transfer.stock_transfer')]),
            'stock_transfer' => StockTransferResource::make($stockTransfer),
        ]);
    }

    public function show(string $stockTransfer, StockTransferService $service)
    {
        $stockTransfer = StockTransfer::findByUuidOrFail($stockTransfer);

        $this->authorize('view', $stockTransfer);

        $stockTransfer->load(['inventory', 'from' => fn ($q) => $q->withFloorAndBlock(), 'to' => fn ($q) => $q->withFloorAndBlock(), 'items.item.category', 'media']);

        return StockTransferResource::make($stockTransfer);
    }

    public function update(StockTransferRequest $request, string $stockTransfer, StockTransferService $service)
    {
        $stockTransfer = StockTransfer::findByUuidOrFail($stockTransfer);

        $this->authorize('update', $stockTransfer);

        $service->update($request, $stockTransfer);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('inventory.stock_transfer.stock_transfer')]),
        ]);
    }

    public function destroy(string $stockTransfer, StockTransferService $service)
    {
        $stockTransfer = StockTransfer::findByUuidOrFail($stockTransfer);

        $this->authorize('delete', $stockTransfer);

        $service->deletable($stockTransfer);

        $service->delete($stockTransfer);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('inventory.stock_transfer.stock_transfer')]),
        ]);
    }

    public function downloadMedia(string $stockTransfer, string $uuid, StockTransferService $service)
    {
        $stockTransfer = StockTransfer::findByUuidOrFail($stockTransfer);

        $this->authorize('view', $stockTransfer);

        return $stockTransfer->downloadMedia($uuid);
    }
}
