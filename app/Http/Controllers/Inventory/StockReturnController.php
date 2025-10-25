<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StockReturnRequest;
use App\Http\Resources\Inventory\StockReturnResource;
use App\Models\Inventory\StockReturn;
use App\Services\Inventory\StockReturnListService;
use App\Services\Inventory\StockReturnService;
use Illuminate\Http\Request;

class StockReturnController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, StockReturnService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, StockReturnListService $service)
    {
        $this->authorize('viewAny', StockReturn::class);

        return $service->paginate($request);
    }

    public function store(StockReturnRequest $request, StockReturnService $service)
    {
        $this->authorize('create', StockReturn::class);

        $stockReturn = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('inventory.stock_return.stock_return')]),
            'stock_return' => StockReturnResource::make($stockReturn),
        ]);
    }

    public function show(string $stockReturn, StockReturnService $service)
    {
        $stockReturn = StockReturn::findByUuidOrFail($stockReturn);

        $this->authorize('view', $stockReturn);

        $stockReturn->load(['inventory', 'place' => fn ($q) => $q->withFloorAndBlock(), 'vendor', 'items.item.category', 'media']);

        return StockReturnResource::make($stockReturn);
    }

    public function update(StockReturnRequest $request, string $stockReturn, StockReturnService $service)
    {
        $stockReturn = StockReturn::findByUuidOrFail($stockReturn);

        $this->authorize('update', $stockReturn);

        $service->update($request, $stockReturn);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('inventory.stock_return.stock_return')]),
        ]);
    }

    public function destroy(string $stockReturn, StockReturnService $service)
    {
        $stockReturn = StockReturn::findByUuidOrFail($stockReturn);

        $this->authorize('delete', $stockReturn);

        $service->deletable($stockReturn);

        $service->delete($stockReturn);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('inventory.stock_return.stock_return')]),
        ]);
    }

    public function downloadMedia(string $stockReturn, string $uuid, StockReturnService $service)
    {
        $stockReturn = StockReturn::findByUuidOrFail($stockReturn);

        $this->authorize('view', $stockReturn);

        return $stockReturn->downloadMedia($uuid);
    }
}
