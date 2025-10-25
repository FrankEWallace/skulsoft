<?php

namespace App\Services\Inventory;

use App\Http\Resources\Asset\Building\RoomResource;
use App\Http\Resources\Finance\LedgerResource;
use App\Http\Resources\Inventory\InventoryResource;
use App\Models\Asset\Building\Room;
use App\Models\Finance\Ledger;
use App\Models\Inventory\Inventory;
use App\Models\Inventory\StockItem;
use App\Models\Inventory\StockItemRecord;
use App\Models\Inventory\StockRequisition;
use App\Support\FormatCodeNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class StockRequisitionService
{
    use FormatCodeNumber;

    private function codeNumber(): array
    {
        $numberPrefix = config('config.inventory.stock_requisition_number_prefix');
        $numberSuffix = config('config.inventory.stock_requisition_number_suffix');
        $digit = config('config.inventory.stock_requisition_number_digit', 0);

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $numberFormat = $this->preFormatForDate($numberFormat);

        $codeNumber = (int) StockRequisition::query()
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

    public function create(Request $request): StockRequisition
    {
        \DB::beginTransaction();

        $stockRequisition = StockRequisition::forceCreate($this->formatParams($request));

        $this->updateItems($request, $stockRequisition);

        $stockRequisition->addMedia($request);

        \DB::commit();

        return $stockRequisition;
    }

    private function updateItems(Request $request, StockRequisition $stockRequisition): void
    {
        $stockItemIds = [];
        foreach ($request->items as $item) {
            $stockItemIds[] = Arr::get($item, 'stock_item_id');

            $stockItemRecord = StockItemRecord::firstOrCreate([
                'model_type' => 'StockRequisition',
                'model_id' => $stockRequisition->id,
                'stock_item_id' => Arr::get($item, 'stock_item_id'),
            ]);

            $stockItem = StockItem::find(Arr::get($item, 'stock_item_id'));

            $stockItemRecord->uuid = Arr::get($item, 'uuid');
            $stockItemRecord->stock_item_id = Arr::get($item, 'stock_item_id');
            $stockItemRecord->description = Arr::get($item, 'description');
            $stockItemRecord->quantity = Arr::get($item, 'quantity', 0);
            $stockItemRecord->unit_price = Arr::get($item, 'unit_price', 0);
            $stockItemRecord->amount = Arr::get($item, 'amount', 0);
            $stockItemRecord->save();
        }

        StockItemRecord::query()
            ->whereModelType('StockRequisition')
            ->whereModelId($stockRequisition->id)
            ->whereNotIn('stock_item_id', $stockItemIds)
            ->delete();
    }

    private function formatParams(Request $request, ?StockRequisition $stockRequisition = null): array
    {
        $formatted = [
            'date' => $request->date,
            'employee_id' => $request->employee_id,
            'inventory_id' => $request->inventory_id,
            'vendor_id' => $request->vendor_id,
            'place_type' => 'Room',
            'place_id' => $request->place_id,
            'total' => $request->total ?? 0,
            'message_to_vendor' => $request->message_to_vendor,
            'description' => $request->description,
        ];

        if (! $stockRequisition) {
            $codeNumberDetail = $this->codeNumber();

            $formatted['number_format'] = Arr::get($codeNumberDetail, 'number_format');
            $formatted['number'] = Arr::get($codeNumberDetail, 'number');
            $formatted['code_number'] = Arr::get($codeNumberDetail, 'code_number');
        }

        return $formatted;
    }

    public function update(Request $request, StockRequisition $stockRequisition): void
    {
        \DB::beginTransaction();

        $stockRequisition->forceFill($this->formatParams($request, $stockRequisition))->save();

        $this->updateItems($request, $stockRequisition);

        $stockRequisition->updateMedia($request);

        \DB::commit();
    }

    public function deletable(StockRequisition $stockRequisition): void
    {
        $stockRequisitionExists = StockRequisition::query()
            ->where('id', '!=', $stockRequisition->id)
            ->where('date', '>=', $stockRequisition->date->value)
            ->exists();

        if ($stockRequisitionExists) {
            throw ValidationException::withMessages(['message' => trans('inventory.stock_requisition.could_not_delete_if_requisition_exists_after_this_date')]);
        }
    }

    public function delete(StockRequisition $stockRequisition): void
    {
        \DB::beginTransaction();

        foreach ($stockRequisition->items as $item) {
            $item->delete();
        }

        $stockRequisition->delete();

        \DB::commit();
    }
}
