<?php

namespace App\Services\Inventory;

use App\Http\Resources\Asset\Building\RoomResource;
use App\Http\Resources\Inventory\InventoryResource;
use App\Models\Asset\Building\Room;
use App\Models\Inventory\Inventory;
use App\Models\Inventory\StockBalance;
use App\Models\Inventory\StockItem;
use App\Models\Inventory\StockItemRecord;
use App\Models\Inventory\StockTransfer;
use App\Support\FormatCodeNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class StockTransferService
{
    use FormatCodeNumber;

    private function codeNumber(): array
    {
        $numberPrefix = config('config.inventory.stock_transfer_number_prefix');
        $numberSuffix = config('config.inventory.stock_transfer_number_suffix');
        $digit = config('config.inventory.stock_transfer_number_digit', 0);

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $numberFormat = $this->preFormatForDate($numberFormat);

        $codeNumber = (int) StockTransfer::query()
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

    public function create(Request $request): StockTransfer
    {
        \DB::beginTransaction();

        $stockTransfer = StockTransfer::forceCreate($this->formatParams($request));

        $this->updateItems($request, $stockTransfer);

        $stockTransfer->addMedia($request);

        \DB::commit();

        return $stockTransfer;
    }

    private function updateItems(Request $request, StockTransfer $stockTransfer): void
    {
        $stockItemIds = [];
        foreach ($request->items as $item) {
            $stockItemIds[] = Arr::get($item, 'stock_item_id');

            $stockItemRecord = StockItemRecord::firstOrCreate([
                'model_type' => 'StockTransfer',
                'model_id' => $stockTransfer->id,
                'stock_item_id' => Arr::get($item, 'stock_item_id'),
            ]);

            $fromStockBalance = StockBalance::query()
                ->wherePlaceType($stockTransfer->from_type)
                ->wherePlaceId($stockTransfer->from_id)
                ->whereStockItemId($stockItemRecord->stock_item_id)
                ->first();

            $toStockBalance = StockBalance::query()
                ->wherePlaceType($stockTransfer->to_type)
                ->wherePlaceId($stockTransfer->to_id)
                ->whereStockItemId($stockItemRecord->stock_item_id)
                ->first();

            $stockItem = StockItem::find(Arr::get($item, 'stock_item_id'));

            $stockItemRecord->uuid = Arr::get($item, 'uuid');
            $stockItemRecord->stock_item_id = Arr::get($item, 'stock_item_id');
            $stockItemRecord->description = Arr::get($item, 'description');
            $stockItemRecord->quantity = Arr::get($item, 'quantity');
            $stockItemRecord->save();

            if (! $fromStockBalance) {
                $fromStockBalance = StockBalance::forceCreate([
                    'place_type' => $stockTransfer->from_type,
                    'place_id' => $stockTransfer->from_id,
                    'stock_item_id' => $stockItemRecord->stock_item_id,
                    'current_quantity' => $stockItemRecord->quantity * -1,
                ]);
            } else {
                $fromStockBalance->current_quantity -= $stockItemRecord->quantity;
                $fromStockBalance->save();
            }

            if (! $toStockBalance) {
                $toStockBalance = StockBalance::forceCreate([
                    'place_type' => $stockTransfer->to_type,
                    'place_id' => $stockTransfer->to_id,
                    'stock_item_id' => $stockItemRecord->stock_item_id,
                    'current_quantity' => $stockItemRecord->quantity,
                ]);
            } else {
                $toStockBalance->current_quantity += $stockItemRecord->quantity;
                $toStockBalance->save();
            }
        }

        StockItemRecord::query()
            ->whereModelType('StockTransfer')
            ->whereModelId($stockTransfer->id)
            ->whereNotIn('stock_item_id', $stockItemIds)
            ->delete();
    }

    private function reverseBalance(StockTransfer $stockTransfer): void
    {
        foreach ($stockTransfer->items as $item) {
            $fromStockBalance = StockBalance::query()
                ->wherePlaceType($stockTransfer->from_type)
                ->wherePlaceId($stockTransfer->from_id)
                ->whereStockItemId($item->stock_item_id)
                ->first();

            if ($fromStockBalance) {
                $fromStockBalance->current_quantity += $item->quantity;
                $fromStockBalance->save();
            }

            $toStockBalance = StockBalance::query()
                ->wherePlaceType($stockTransfer->to_type)
                ->wherePlaceId($stockTransfer->to_id)
                ->whereStockItemId($item->stock_item_id)
                ->first();

            if ($toStockBalance) {
                $toStockBalance->current_quantity -= $item->quantity;
                $toStockBalance->save();
            }
        }
    }

    private function formatParams(Request $request, ?StockTransfer $stockTransfer = null): array
    {
        $formatted = [
            'inventory_id' => $request->inventory_id,
            'date' => $request->date,
            'return_due_date' => $request->return_due_date,
            'from_type' => 'Room',
            'from_id' => $request->from_id,
            'to_type' => 'Room',
            'to_id' => $request->to_id,
            'description' => $request->description,
        ];

        if (! $stockTransfer) {
            $codeNumberDetail = $this->codeNumber();

            $formatted['number_format'] = Arr::get($codeNumberDetail, 'number_format');
            $formatted['number'] = Arr::get($codeNumberDetail, 'number');
            $formatted['code_number'] = Arr::get($codeNumberDetail, 'code_number');
        }

        return $formatted;
    }

    public function update(Request $request, StockTransfer $stockTransfer): void
    {
        \DB::beginTransaction();

        $this->reverseBalance($stockTransfer);

        $stockTransfer->forceFill($this->formatParams($request, $stockTransfer))->save();

        $this->updateItems($request, $stockTransfer);

        $stockTransfer->updateMedia($request);

        \DB::commit();
    }

    public function deletable(StockTransfer $stockTransfer): void
    {
        $stockTransferExists = StockTransfer::query()
            ->where('id', '!=', $stockTransfer->id)
            ->where('date', '>=', $stockTransfer->date->value)
            ->exists();

        if ($stockTransferExists) {
            throw ValidationException::withMessages(['message' => trans('inventory.stock_transfer.could_not_delete_if_transfer_exists_after_this_date')]);
        }
    }

    public function delete(StockTransfer $stockTransfer): void
    {
        \DB::beginTransaction();

        foreach ($stockTransfer->items as $item) {
            $fromStockBalance = StockBalance::query()
                ->wherePlaceType($stockTransfer->from_type)
                ->wherePlaceId($stockTransfer->from_id)
                ->whereStockItemId($item->stock_item_id)
                ->first();

            if ($fromStockBalance) {
                $fromStockBalance->current_quantity += $item->quantity;
                $fromStockBalance->save();
            }

            $toStockBalance = StockBalance::query()
                ->wherePlaceType($stockTransfer->to_type)
                ->wherePlaceId($stockTransfer->to_id)
                ->whereStockItemId($item->stock_item_id)
                ->first();

            if ($toStockBalance) {
                $toStockBalance->current_quantity -= $item->quantity;
                $toStockBalance->save();
            }

            $item->delete();
        }

        $stockTransfer->delete();

        \DB::commit();
    }
}
