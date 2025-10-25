<?php

namespace App\Http\Requests\Inventory;

use App\Models\Inventory\Inventory;
use App\Models\Inventory\StockCategory;
use Illuminate\Foundation\Http\FormRequest;

class StockCategoryRequest extends FormRequest
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
            'name' => ['required', 'min:2', 'max:100'],
            'inventory' => 'required|uuid',
            'description' => ['nullable', 'min:2', 'max:100'],
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {

            $stockCategoryUuid = $this->route('stock_category');

            $inventory = Inventory::query()
                ->byTeam()
                ->filterAccessible()
                ->whereUuid($this->inventory)
                ->getOrFail(__('inventory.inventory'), 'inventory');

            $existingRecord = StockCategory::query()
                ->byTeam()
                ->when($stockCategoryUuid, function ($q, $stockCategoryUuid) {
                    $q->where('uuid', '!=', $stockCategoryUuid);
                })
                ->whereInventoryId($inventory?->id)
                ->where('name', $this->name)
                ->exists();

            if ($existingRecord) {
                $validator->errors()->add('name', __('validation.unique', ['attribute' => __('inventory.stock_category.props.name')]));
            }

            $this->merge([
                'inventory_id' => $inventory?->id,
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
            'name' => __('inventory.stock_category.props.name'),
            'inventory' => __('inventory.inventory'),
            'description' => __('inventory.stock_category.props.description'),
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
