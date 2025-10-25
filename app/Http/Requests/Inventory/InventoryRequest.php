<?php

namespace App\Http\Requests\Inventory;

use App\Models\Inventory\Inventory;
use Illuminate\Foundation\Http\FormRequest;

class InventoryRequest extends FormRequest
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
            'description' => ['nullable', 'min:2', 'max:100'],
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {

            $inventoryUuid = $this->route('inventory');

            $existingRecord = Inventory::query()
                ->byTeam()
                ->when($inventoryUuid, function ($q, $inventoryUuid) {
                    $q->where('uuid', '!=', $inventoryUuid);
                })
                ->where('name', $this->name)
                ->exists();

            if ($existingRecord) {
                $validator->errors()->add('name', __('validation.unique', ['attribute' => __('inventory.props.name')]));
            }

            $this->merge([
                //
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
            'name' => __('inventory.props.name'),
            'description' => __('inventory.props.description'),
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
