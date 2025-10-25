<?php

namespace App\Services\Inventory;

use App\Http\Resources\Inventory\InventoryResource;
use App\Models\Inventory\Inventory;
use App\Models\Inventory\StockCategory;
use App\Models\Inventory\StockItem;
use App\Models\Inventory\StockItemRecord;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StockCategoryService
{
    public function preRequisite(Request $request)
    {
        $inventories = InventoryResource::collection(Inventory::query()
            ->byTeam()
            ->filterAccessible()
            ->get());

        return compact('inventories');
    }

    public function create(Request $request): StockCategory
    {
        \DB::beginTransaction();

        $stockCategory = StockCategory::forceCreate($this->formatParams($request));

        \DB::commit();

        return $stockCategory;
    }

    private function formatParams(Request $request, ?StockCategory $stockCategory = null): array
    {
        $formatted = [
            'name' => $request->name,
            'inventory_id' => $request->inventory_id,
            'description' => $request->description,
        ];

        if (! $stockCategory) {
            //
        }

        return $formatted;
    }

    public function update(Request $request, StockCategory $stockCategory): void
    {
        $this->validateChangeInventory($request, $stockCategory);

        \DB::beginTransaction();

        $stockCategory->forceFill($this->formatParams($request, $stockCategory))->save();

        \DB::commit();
    }

    private function validateChangeInventory(Request $request, StockCategory $stockCategory)
    {
        if ($request->inventory_id == $stockCategory->inventory_id) {
            return;
        }

        $stockItemIds = StockItem::query()
            ->whereStockCategoryId($stockCategory->id)
            ->pluck('id')
            ->toArray();

        $stockTransactionExists = StockItemRecord::query()
            ->whereIn('stock_item_id', $stockItemIds)
            ->exists();

        if ($stockTransactionExists) {
            throw ValidationException::withMessages(['message' => trans('inventory.stock_category.could_not_change_inventory_after_transaction')]);
        }
    }

    public function deletable(StockCategory $stockCategory): bool
    {
        $stockItemExists = \DB::table('stock_items')
            ->whereStockCategoryId($stockCategory->id)
            ->exists();

        if ($stockItemExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('inventory.stock_category.stock_category'), 'dependency' => trans('inventory.stock_item.stock_item')])]);
        }

        return true;
    }
}
