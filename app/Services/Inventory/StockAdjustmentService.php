<?php

namespace App\Services\Inventory;

use App\Http\Resources\Asset\Building\RoomResource;
use App\Http\Resources\Inventory\InventoryResource;
use App\Models\Asset\Building\Room;
use App\Models\Inventory\Inventory;
use App\Models\Inventory\StockAdjustment;
use App\Models\Inventory\StockBalance;
use App\Models\Inventory\StockItem;
use App\Models\Inventory\StockItemRecord;
use App\Support\FormatCodeNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class StockAdjustmentService
{
    use FormatCodeNumber;

    private function codeNumber(): array
    {
        $numberPrefix = config('config.inventory.stock_adjustment_number_prefix');
        $numberSuffix = config('config.inventory.stock_adjustment_number_suffix');
        $digit = config('config.inventory.stock_adjustment_number_digit', 0);

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $numberFormat = $this->preFormatForDate($numberFormat);

        $codeNumber = (int) StockAdjustment::query()
            ->byTeam()
            ->whereNumberFormat($numberFormat)
            ->max('number') + 1;

        return $this->getCodeNumber(number: $codeNumber, digit: $digit, format: $numberFormat);
    }

    public function preRequisite(Request $request)
    {
        $inventories = InventoryResource::collection(Inventory::query()
            ->byTeam()
            ->get());

        $places = RoomResource::collection(Room::query()
            ->withFloorAndBlock()
            ->get());

        return compact('inventories', 'places');
    }

    public function create(Request $request): StockAdjustment
    {
        \DB::beginTransaction();

        $stockAdjustment = StockAdjustment::forceCreate($this->formatParams($request));

        $this->updateItems($request, $stockAdjustment);

        $stockAdjustment->addMedia($request);

        \DB::commit();

        return $stockAdjustment;
    }

    private function updateItems(Request $request, StockAdjustment $stockAdjustment): void
    {
        $stockItemIds = [];
        foreach ($request->items as $item) {
            $stockItemIds[] = Arr::get($item, 'stock_item_id');

            $stockItemRecord = StockItemRecord::firstOrCreate([
                'model_type' => 'StockAdjustment',
                'model_id' => $stockAdjustment->id,
                'stock_item_id' => Arr::get($item, 'stock_item_id'),
            ]);

            $stockBalance = StockBalance::query()
                ->wherePlaceType($stockAdjustment->place_type)
                ->wherePlaceId($stockAdjustment->place_id)
                ->whereStockItemId($stockItemRecord->stock_item_id)
                ->first();

            $stockItem = StockItem::find(Arr::get($item, 'stock_item_id'));

            $stockItemRecord->uuid = Arr::get($item, 'uuid');
            $stockItemRecord->stock_item_id = Arr::get($item, 'stock_item_id');
            $stockItemRecord->description = Arr::get($item, 'description');
            $stockItemRecord->quantity = Arr::get($item, 'quantity');
            $stockItemRecord->unit_price = Arr::get($item, 'unit_price');
            $stockItemRecord->save();

            if (! $stockBalance) {
                $stockBalance = StockBalance::forceCreate([
                    'place_type' => $stockAdjustment->place_type,
                    'place_id' => $stockAdjustment->place_id,
                    'stock_item_id' => $stockItemRecord->stock_item_id,
                    'current_quantity' => $stockItemRecord->quantity,
                ]);
            } else {
                $stockBalance->current_quantity += $stockItemRecord->quantity;
                $stockBalance->save();
            }
        }

        StockItemRecord::query()
            ->whereModelType('StockAdjustment')
            ->whereModelId($stockAdjustment->id)
            ->whereNotIn('stock_item_id', $stockItemIds)
            ->delete();
    }

    private function reverseBalance(StockAdjustment $stockAdjustment): void
    {
        foreach ($stockAdjustment->items as $item) {
            $stockBalance = StockBalance::query()
                ->wherePlaceType($stockAdjustment->place_type)
                ->wherePlaceId($stockAdjustment->place_id)
                ->whereStockItemId($item->stock_item_id)
                ->first();

            if ($stockBalance) {
                $stockBalance->current_quantity -= $item->quantity;
                $stockBalance->save();
            }
        }
    }

    private function formatParams(Request $request, ?StockAdjustment $stockAdjustment = null): array
    {
        $formatted = [
            'inventory_id' => $request->inventory_id,
            'date' => $request->date,
            'place_type' => 'Room',
            'place_id' => $request->place_id,
            'description' => $request->description,
        ];

        if (! $stockAdjustment) {
            $codeNumberDetail = $this->codeNumber();

            $formatted['number_format'] = Arr::get($codeNumberDetail, 'number_format');
            $formatted['number'] = Arr::get($codeNumberDetail, 'number');
            $formatted['code_number'] = Arr::get($codeNumberDetail, 'code_number');
        }

        return $formatted;
    }

    public function update(Request $request, StockAdjustment $stockAdjustment): void
    {
        \DB::beginTransaction();

        $this->reverseBalance($stockAdjustment);

        $stockAdjustment->forceFill($this->formatParams($request, $stockAdjustment))->save();

        $this->updateItems($request, $stockAdjustment);

        $stockAdjustment->updateMedia($request);

        \DB::commit();
    }

    public function deletable(StockAdjustment $stockAdjustment): void
    {
        $stockAdjustmentExists = StockAdjustment::query()
            ->where('id', '!=', $stockAdjustment->id)
            ->where('date', '>=', $stockAdjustment->date->value)
            ->exists();

        if ($stockAdjustmentExists) {
            throw ValidationException::withMessages(['message' => trans('inventory.stock_adjustment.could_not_delete_if_adjustment_exists_after_this_date')]);
        }
    }

    public function delete(StockAdjustment $stockAdjustment): void
    {
        \DB::beginTransaction();

        foreach ($stockAdjustment->items as $item) {
            $stockBalance = StockBalance::query()
                ->wherePlaceType($stockAdjustment->place_type)
                ->wherePlaceId($stockAdjustment->place_id)
                ->whereStockItemId($item->stock_item_id)
                ->first();

            if ($stockBalance) {
                $stockBalance->current_quantity -= $item->quantity;
                $stockBalance->save();
            }

            $item->delete();
        }

        $stockAdjustment->delete();

        \DB::commit();
    }
}
