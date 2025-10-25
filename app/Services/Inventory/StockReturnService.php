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
use App\Models\Inventory\StockReturn;
use App\Support\FormatCodeNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class StockReturnService
{
    use FormatCodeNumber;

    private function codeNumber(): array
    {
        $numberPrefix = config('config.inventory.stock_return_number_prefix');
        $numberSuffix = config('config.inventory.stock_return_number_suffix');
        $digit = config('config.inventory.stock_return_number_digit', 0);

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $numberFormat = $this->preFormatForDate($numberFormat);

        $codeNumber = (int) StockReturn::query()
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

    public function create(Request $request): StockReturn
    {
        \DB::beginTransaction();

        $stockReturn = StockReturn::forceCreate($this->formatParams($request));

        if ($request->boolean('has_items')) {
            $this->updateItems($request, $stockReturn);
        }

        $ledger = $request->ledger;
        $ledger->updateSecondaryBalance(TransactionType::PAYMENT, $stockReturn->total->value);

        $stockReturn->addMedia($request);

        \DB::commit();

        return $stockReturn;
    }

    private function updateItems(Request $request, StockReturn $stockReturn): void
    {
        $stockItemIds = [];
        foreach ($request->items as $item) {
            $stockItemIds[] = Arr::get($item, 'stock_item_id');

            $stockItemRecord = StockItemRecord::firstOrCreate([
                'model_type' => 'StockReturn',
                'model_id' => $stockReturn->id,
                'stock_item_id' => Arr::get($item, 'stock_item_id'),
            ]);

            $stockBalance = StockBalance::query()
                ->wherePlaceType($stockReturn->place_type)
                ->wherePlaceId($stockReturn->place_id)
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
                    'place_type' => $stockReturn->place_type,
                    'place_id' => $stockReturn->place_id,
                    'stock_item_id' => $stockItemRecord->stock_item_id,
                    'current_quantity' => $stockItemRecord->quantity,
                ]);
            } else {
                $stockBalance->current_quantity -= $stockItemRecord->quantity;
                $stockBalance->save();
            }
        }

        StockItemRecord::query()
            ->whereModelType('StockReturn')
            ->whereModelId($stockReturn->id)
            ->whereNotIn('stock_item_id', $stockItemIds)
            ->delete();
    }

    private function reverseBalance(StockReturn $stockReturn): void
    {
        foreach ($stockReturn->items as $item) {
            $stockBalance = StockBalance::query()
                ->wherePlaceType($stockReturn->place_type)
                ->wherePlaceId($stockReturn->place_id)
                ->whereStockItemId($item->stock_item_id)
                ->first();

            if ($stockBalance) {
                $stockBalance->current_quantity += $item->quantity;
                $stockBalance->save();
            }
        }
    }

    private function formatParams(Request $request, ?StockReturn $stockReturn = null): array
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

        $meta = $stockReturn?->meta ?? [];
        $meta['has_items'] = $request->boolean('has_items');
        $formatted['meta'] = $meta;

        if (! $stockReturn) {
            $codeNumberDetail = $this->codeNumber();

            $formatted['number_format'] = Arr::get($codeNumberDetail, 'number_format');
            $formatted['number'] = Arr::get($codeNumberDetail, 'number');
            $formatted['code_number'] = Arr::get($codeNumberDetail, 'code_number');
        }

        return $formatted;
    }

    public function update(Request $request, StockReturn $stockReturn): void
    {
        $updateLedger = false;
        if ($stockReturn->total->value != $request->total || $stockReturn->vendor_id != $request->vendor_id) {
            $updateLedger = true;
        }

        \DB::beginTransaction();

        if ($updateLedger) {
            $ledger = $stockReturn->vendor;
            $ledger->reverseSecondaryBalance(TransactionType::PAYMENT, $stockReturn->total->value);
        }

        $this->reverseBalance($stockReturn);

        $stockReturn->forceFill($this->formatParams($request, $stockReturn))->save();

        $this->updateItems($request, $stockReturn);

        if ($updateLedger) {
            $ledger = $request->ledger;
            $ledger->updateSecondaryBalance(TransactionType::PAYMENT, $request->total);
        }

        $stockReturn->updateMedia($request);

        \DB::commit();
    }

    public function deletable(StockReturn $stockReturn): void
    {
        $stockReturnExists = StockReturn::query()
            ->where('id', '!=', $stockReturn->id)
            ->where('date', '>=', $stockReturn->date->value)
            ->exists();

        if ($stockReturnExists) {
            throw ValidationException::withMessages(['message' => trans('inventory.stock_return.could_not_delete_if_return_exists_after_this_date')]);
        }
    }

    public function delete(StockReturn $stockReturn): void
    {
        \DB::beginTransaction();

        foreach ($stockReturn->items as $item) {
            $stockBalance = StockBalance::query()
                ->wherePlaceType($stockReturn->place_type)
                ->wherePlaceId($stockReturn->place_id)
                ->whereStockItemId($item->stock_item_id)
                ->first();

            if ($stockBalance) {
                $stockBalance->current_quantity += $item->quantity;
                $stockBalance->save();
            }

            $item->delete();
        }

        $ledger = $stockReturn->vendor;
        $ledger->reverseSecondaryBalance(TransactionType::PAYMENT, $stockReturn->total->value);

        $stockReturn->delete();

        \DB::commit();
    }
}
