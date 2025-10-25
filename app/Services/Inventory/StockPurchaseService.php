<?php

namespace App\Services\Inventory;

use App\Enums\Finance\TransactionType;
use App\Http\Resources\Asset\Building\RoomResource;
use App\Http\Resources\Finance\LedgerResource;
use App\Http\Resources\Inventory\InventoryResource;
use App\Models\Asset\Building\Room;
use App\Models\Finance\Ledger;
use App\Models\Inventory\Inventory;
use App\Models\Inventory\StockBalance;
use App\Models\Inventory\StockItem;
use App\Models\Inventory\StockItemRecord;
use App\Models\Inventory\StockPurchase;
use App\Support\FormatCodeNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class StockPurchaseService
{
    use FormatCodeNumber;

    private function codeNumber(): array
    {
        $numberPrefix = config('config.inventory.stock_purchase_number_prefix');
        $numberSuffix = config('config.inventory.stock_purchase_number_suffix');
        $digit = config('config.inventory.stock_purchase_number_digit', 0);

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $numberFormat = $this->preFormatForDate($numberFormat);

        $codeNumber = (int) StockPurchase::query()
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

        $vendors = LedgerResource::collection(Ledger::query()
            ->byTeam()
            ->subType('vendor')
            ->get());

        $places = RoomResource::collection(Room::query()
            ->withFloorAndBlock()
            ->get());

        return compact('inventories', 'vendors', 'places');
    }

    public function create(Request $request): StockPurchase
    {
        \DB::beginTransaction();

        $stockPurchase = StockPurchase::forceCreate($this->formatParams($request));

        if ($request->boolean('has_items')) {
            $this->updateItems($request, $stockPurchase);
        }

        $ledger = $request->ledger;
        $ledger->updateSecondaryBalance(TransactionType::RECEIPT, $stockPurchase->total->value);

        $stockPurchase->addMedia($request);

        \DB::commit();

        return $stockPurchase;
    }

    private function updateItems(Request $request, StockPurchase $stockPurchase): void
    {
        $stockItemIds = [];
        foreach ($request->items as $item) {
            $stockItemIds[] = Arr::get($item, 'stock_item_id');

            $stockItemRecord = StockItemRecord::firstOrCreate([
                'model_type' => 'StockPurchase',
                'model_id' => $stockPurchase->id,
                'stock_item_id' => Arr::get($item, 'stock_item_id'),
            ]);

            $stockBalance = StockBalance::query()
                ->wherePlaceType($stockPurchase->place_type)
                ->wherePlaceId($stockPurchase->place_id)
                ->whereStockItemId($stockItemRecord->stock_item_id)
                ->first();

            $stockItem = StockItem::find(Arr::get($item, 'stock_item_id'));

            $stockItemRecord->uuid = Arr::get($item, 'uuid');
            $stockItemRecord->stock_item_id = Arr::get($item, 'stock_item_id');
            $stockItemRecord->description = Arr::get($item, 'description');
            $stockItemRecord->quantity = Arr::get($item, 'quantity');
            $stockItemRecord->unit_price = Arr::get($item, 'unit_price');
            $stockItemRecord->amount = Arr::get($item, 'amount');
            $stockItemRecord->save();

            if (! $stockBalance) {
                $stockBalance = StockBalance::forceCreate([
                    'place_type' => $stockPurchase->place_type,
                    'place_id' => $stockPurchase->place_id,
                    'stock_item_id' => $stockItemRecord->stock_item_id,
                    'current_quantity' => $stockItemRecord->quantity,
                ]);
            } else {
                $stockBalance->current_quantity += $stockItemRecord->quantity;
                $stockBalance->save();
            }
        }

        StockItemRecord::query()
            ->whereModelType('StockPurchase')
            ->whereModelId($stockPurchase->id)
            ->whereNotIn('stock_item_id', $stockItemIds)
            ->delete();
    }

    private function reverseBalance(StockPurchase $stockPurchase): void
    {
        foreach ($stockPurchase->items as $item) {
            $stockBalance = StockBalance::query()
                ->wherePlaceType($stockPurchase->place_type)
                ->wherePlaceId($stockPurchase->place_id)
                ->whereStockItemId($item->stock_item_id)
                ->first();

            if ($stockBalance) {
                $stockBalance->current_quantity -= $item->quantity;
                $stockBalance->save();
            }
        }
    }

    private function formatParams(Request $request, ?StockPurchase $stockPurchase = null): array
    {
        $formatted = [
            'date' => $request->date,
            'inventory_id' => $request->inventory_id,
            'vendor_id' => $request->vendor_id,
            'place_type' => 'Room',
            'place_id' => $request->place_id,
            'voucher_number' => $request->voucher_number,
            'total' => $request->total,
            'description' => $request->description,
        ];

        $meta = $stockPurchase?->meta ?? [];
        $meta['has_items'] = $request->boolean('has_items');
        $formatted['meta'] = $meta;

        if (! $stockPurchase) {
            $codeNumberDetail = $this->codeNumber();

            $formatted['number_format'] = Arr::get($codeNumberDetail, 'number_format');
            $formatted['number'] = Arr::get($codeNumberDetail, 'number');
            $formatted['code_number'] = Arr::get($codeNumberDetail, 'code_number');
        }

        return $formatted;
    }

    public function update(Request $request, StockPurchase $stockPurchase): void
    {
        $updateLedger = false;
        if ($stockPurchase->total->value != $request->total || $stockPurchase->vendor_id != $request->vendor_id) {
            $updateLedger = true;
        }

        \DB::beginTransaction();

        if ($updateLedger) {
            $ledger = $stockPurchase->vendor;
            $ledger->reverseSecondaryBalance(TransactionType::RECEIPT, $stockPurchase->total->value);
        }

        $this->reverseBalance($stockPurchase);

        $stockPurchase->forceFill($this->formatParams($request, $stockPurchase))->save();

        $this->updateItems($request, $stockPurchase);

        if ($updateLedger) {
            $ledger = $request->ledger;
            $ledger->updateSecondaryBalance(TransactionType::RECEIPT, $request->total);
        }

        $stockPurchase->updateMedia($request);

        \DB::commit();
    }

    public function deletable(StockPurchase $stockPurchase): void
    {
        $stockPurchaseExists = StockPurchase::query()
            ->where('id', '!=', $stockPurchase->id)
            ->where('date', '>=', $stockPurchase->date->value)
            ->exists();

        if ($stockPurchaseExists) {
            throw ValidationException::withMessages(['message' => trans('inventory.stock_purchase.could_not_delete_if_purchase_exists_after_this_date')]);
        }
    }

    public function delete(StockPurchase $stockPurchase): void
    {
        \DB::beginTransaction();

        foreach ($stockPurchase->items as $item) {
            $stockBalance = StockBalance::query()
                ->wherePlaceType($stockPurchase->place_type)
                ->wherePlaceId($stockPurchase->place_id)
                ->whereStockItemId($item->stock_item_id)
                ->first();

            if ($stockBalance) {
                $stockBalance->current_quantity -= $item->quantity;
                $stockBalance->save();
            }

            $item->delete();
        }

        $ledger = $stockPurchase->vendor;
        $ledger->reverseSecondaryBalance(TransactionType::RECEIPT, $stockPurchase->total->value);

        $stockPurchase->delete();

        \DB::commit();
    }
}
