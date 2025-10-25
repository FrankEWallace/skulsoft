<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StockRequisitionRequest;
use App\Http\Resources\Inventory\StockRequisitionResource;
use App\Models\Inventory\StockRequisition;
use App\Services\Inventory\StockRequisitionListService;
use App\Services\Inventory\StockRequisitionService;
use Illuminate\Http\Request;

class StockRequisitionController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, StockRequisitionService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, StockRequisitionListService $service)
    {
        $this->authorize('viewAny', StockRequisition::class);

        return $service->paginate($request);
    }

    public function store(StockRequisitionRequest $request, StockRequisitionService $service)
    {
        $this->authorize('create', StockRequisition::class);

        $stockRequisition = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('inventory.stock_requisition.stock_requisition')]),
            'stock_requisition' => StockRequisitionResource::make($stockRequisition),
        ]);
    }

    public function show(string $stockRequisition, StockRequisitionService $service)
    {
        $stockRequisition = StockRequisition::findByUuidOrFail($stockRequisition);

        $this->authorize('view', $stockRequisition);

        $stockRequisition->load(['inventory', 'place' => fn ($q) => $q->withFloorAndBlock(), 'vendor', 'items.item.category', 'employee' => fn ($q) => $q->summary(), 'media']);

        return StockRequisitionResource::make($stockRequisition);
    }

    public function update(StockRequisitionRequest $request, string $stockRequisition, StockRequisitionService $service)
    {
        $stockRequisition = StockRequisition::findByUuidOrFail($stockRequisition);

        $this->authorize('update', $stockRequisition);

        $service->update($request, $stockRequisition);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('inventory.stock_requisition.stock_requisition')]),
        ]);
    }

    public function destroy(string $stockRequisition, StockRequisitionService $service)
    {
        $stockRequisition = StockRequisition::findByUuidOrFail($stockRequisition);

        $this->authorize('delete', $stockRequisition);

        $service->deletable($stockRequisition);

        $service->delete($stockRequisition);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('inventory.stock_requisition.stock_requisition')]),
        ]);
    }

    public function downloadMedia(string $stockRequisition, string $uuid, StockRequisitionService $service)
    {
        $stockRequisition = StockRequisition::findByUuidOrFail($stockRequisition);

        $this->authorize('view', $stockRequisition);

        return $stockRequisition->downloadMedia($uuid);
    }

    public function export(StockRequisition $stockRequisition, StockRequisitionService $service)
    {
        $this->authorize('view', $stockRequisition);

        $stockRequisition->load(['vendor', 'place', 'items.item', 'employee' => fn ($q) => $q->summary()]);

        $stockRequisition = json_decode(StockRequisitionResource::make($stockRequisition)->toJson(), true);

        return view()->first([config('config.print.custom_path').'inventory.stock-requisition', 'print.inventory.stock-requisition'], compact('stockRequisition'));
    }
}
