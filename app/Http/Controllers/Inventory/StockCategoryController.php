<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StockCategoryRequest;
use App\Http\Resources\Inventory\StockCategoryResource;
use App\Models\Inventory\StockCategory;
use App\Services\Inventory\StockCategoryListService;
use App\Services\Inventory\StockCategoryService;
use Illuminate\Http\Request;

class StockCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, StockCategoryService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, StockCategoryListService $service)
    {
        $this->authorize('viewAny', StockCategory::class);

        return $service->paginate($request);
    }

    public function store(StockCategoryRequest $request, StockCategoryService $service)
    {
        $this->authorize('create', StockCategory::class);

        $stockCategory = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('inventory.stock_category.stock_category')]),
            'stock_category' => StockCategoryResource::make($stockCategory),
        ]);
    }

    public function show(string $stockCategory, StockCategoryService $service)
    {
        $stockCategory = StockCategory::findByUuidOrFail($stockCategory);

        $this->authorize('view', $stockCategory);

        $stockCategory->load('inventory');

        return StockCategoryResource::make($stockCategory);
    }

    public function update(StockCategoryRequest $request, string $stockCategory, StockCategoryService $service)
    {
        $stockCategory = StockCategory::findByUuidOrFail($stockCategory);

        $this->authorize('update', $stockCategory);

        $service->update($request, $stockCategory);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('inventory.stock_category.stock_category')]),
        ]);
    }

    public function destroy(string $stockCategory, StockCategoryService $service)
    {
        $stockCategory = StockCategory::findByUuidOrFail($stockCategory);

        $this->authorize('delete', $stockCategory);

        $service->deletable($stockCategory);

        $stockCategory->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('inventory.stock_category.stock_category')]),
        ]);
    }
}
