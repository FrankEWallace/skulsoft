<?php

namespace App\Http\Requests\Inventory;

use App\Models\Asset\Building\Room;
use App\Models\Employee\Employee;
use App\Models\Finance\Ledger;
use App\Models\Inventory\Inventory;
use App\Models\Inventory\StockItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StockRequisitionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'inventory' => 'required|uuid',
            'vendor' => 'nullable|uuid',
            'employee' => 'nullable|uuid',
            'date' => ['required', 'date_format:Y-m-d'],
            'place' => 'nullable|uuid',
            'description' => ['nullable', 'min:2', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.uuid' => ['required', 'uuid'],
            'items.*.item' => ['required', 'array'],
            'items.*.item.uuid' => ['required', 'uuid'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            // 'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.description' => ['nullable', 'min:2', 'max:100'],
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {

            $stockRequisitionUuid = $this->route('stock_requisition');

            $inventory = Inventory::query()
                ->byTeam()
                ->filterAccessible()
                ->where('uuid', $this->inventory)
                ->getOrFail(__('inventory.inventory'), 'inventory');

            $vendor = $this->vendor ? Ledger::query()
                ->byTeam()
                ->subType('vendor')
                ->where('uuid', $this->vendor)
                ->getOrFail(__('inventory.vendor.vendor'), 'vendor') : null;

            $employee = $this->employee ? Employee::query()
                ->summary()
                ->where('employees.uuid', $this->employee)
                ->getOrFail(__('employee.employee'), 'employee') : null;

            $place = $this->place ? Room::query()
                ->withFloorAndBlock()
                ->where('rooms.uuid', $this->place)
                ->getOrFail(__('inventory.place'), 'place') : null;

            $stockItems = StockItem::query()
                ->whereHas('category', function ($q) {
                    $q->whereHas('inventory', function ($q) {
                        $q->where('uuid', $this->inventory);
                    });
                })
                ->select('uuid', 'id')
                ->get();

            $total = 0;
            $newItems = [];
            foreach ($this->items as $index => $item) {
                $stockItemId = null;
                $selectedItem = $stockItems->where('uuid', Arr::get($item, 'item.uuid'))->first();

                if (! $selectedItem) {
                    throw ValidationException::withMessages(['items.'.$index.'.item' => trans('validation.exists', ['attribute' => __('inventory.stock_item.stock_item')])]);
                }

                $stockItemId = $selectedItem?->id;

                $quantity = round(Arr::get($item, 'quantity', 1), 2);
                // $unitPrice = Arr::get($item, 'unit_price', 0);
                // $amount = \Price::from($quantity * $unitPrice)->value;

                // $total += $amount;

                $newItems[] = [
                    'uuid' => (string) Str::uuid(),
                    'stock_item_id' => $stockItemId,
                    'quantity' => $quantity,
                    // 'unit_price' => $unitPrice,
                    // 'amount' => $amount,
                    'description' => Arr::get($item, 'description'),
                ];
            }

            $this->merge([
                'employee_id' => $employee?->id,
                'inventory_id' => $inventory?->id,
                'vendor_id' => $vendor?->id,
                'place_id' => $place?->id,
                'items' => $newItems,
                'total' => $total ?? 0,
            ]);
        });
    }

    /**
     * Translate fields with user friendly name.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'inventory' => __('inventory.inventory'),
            'vendor' => __('inventory.vendor.vendor'),
            'place' => __('inventory.place'),
            'date' => __('inventory.stock_requisition.props.date'),
            'description' => __('inventory.stock_requisition.props.description'),
            'items.*.item' => __('inventory.stock_item.stock_item'),
            'items.*.quantity' => __('inventory.stock_requisition.props.quantity'),
            // 'items.*.unit_price' => __('inventory.stock_requisition.props.unit_price'),
            'items.*.description' => __('inventory.stock_requisition.props.description'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }
}
