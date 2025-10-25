<?php

namespace App\Services\Inventory;

use App\Http\Resources\Asset\Building\RoomResource;
use App\Http\Resources\Inventory\InventoryResource;
use App\Http\Resources\Inventory\StockCategoryResource;
use App\Models\Asset\Building\Room;
use App\Models\Inventory\Inventory;
use App\Models\Inventory\StockBalance;
use App\Models\Inventory\StockCategory;
use App\Models\Inventory\StockItem;
use App\Models\Inventory\StockItemRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StockItemService
{
    public function preRequisite(Request $request)
    {
        $inventories = InventoryResource::collection(Inventory::query()
            ->byTeam()
            ->filterAccessible()
            ->get());

        $categories = StockCategoryResource::collection(StockCategory::query()
            ->byTeam()
            ->filterAccessible()
            ->get());

        $places = RoomResource::collection(Room::query()
            ->byTeam()
            ->withFloorAndBlock()
            ->get());

        return compact('inventories', 'categories', 'places');
    }

    public function create(Request $request): StockItem
    {
        \DB::beginTransaction();

        $stockItem = StockItem::forceCreate($this->formatParams($request));

        if ($request->place_id) {
            $stockBalance = StockBalance::forceCreate([
                'place_type' => 'Room',
                'place_id' => $request->place_id,
                'stock_item_id' => $stockItem->id,
                'opening_quantity' => $request->quantity,
            ]);
        }

        \DB::commit();

        return $stockItem;
    }

    private function formatParams(Request $request, ?StockItem $stockItem = null): array
    {
        $formatted = [
            'name' => $request->name,
            'code' => Str::upper($request->code),
            'stock_category_id' => $request->stock_category_id,
            'unit' => $request->unit,
            'description' => $request->description,
        ];

        if (! $stockItem) {

        }

        return $formatted;
    }

    public function update(Request $request, StockItem $stockItem): void
    {
        $this->validateChangeInventory($request, $stockItem);

        \DB::beginTransaction();

        $stockItem->forceFill($this->formatParams($request, $stockItem))->save();

        if ($stockItem->is_quantity_editable) {
            $stockBalance = StockBalance::query()
                ->whereStockItemId($stockItem->id)
                ->first();

            if (! $stockBalance) {
                $stockBalance = StockBalance::forceCreate([
                    'place_type' => 'Room',
                    'place_id' => $request->place_id,
                    'stock_item_id' => $stockItem->id,
                    'opening_quantity' => $request->quantity,
                ]);
            } else {
                $stockBalance->update([
                    'place_id' => $request->place_id,
                    'opening_quantity' => $request->quantity,
                ]);
            }
        }

        \DB::commit();
    }

    private function validateChangeInventory(Request $request, StockItem $stockItem)
    {
        $newStockCategory = StockCategory::query()
            ->whereId($request->stock_category_id)
            ->firstOrFail();

        if ($newStockCategory->inventory_id == $stockItem->category->inventory_id) {
            return;
        }

        $stockTransactionExists = StockItemRecord::query()
            ->where('stock_item_id', $stockItem->id)
            ->exists();

        if ($stockTransactionExists) {
            throw ValidationException::withMessages(['message' => trans('inventory.stock_item.could_not_change_inventory_after_transaction')]);
        }
    }

    public function deletable(StockItem $stockItem): void
    {
        $transactionExists = \DB::table('stock_item_records')
            ->whereStockItemId($stockItem->id)
            ->exists();

        if ($transactionExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('inventory.stock_item.stock_item'), 'dependency' => trans('inventory.transaction')])]);
        }
    }
}
