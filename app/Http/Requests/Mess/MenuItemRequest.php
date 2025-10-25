<?php

namespace App\Http\Requests\Mess;

use App\Models\Mess\MenuItem;
use Illuminate\Foundation\Http\FormRequest;

class MenuItemRequest extends FormRequest
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
            'name' => ['required', 'min:3', 'max:255'],
            'description' => ['nullable', 'min:3', 'max:1000'],
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {

            $menuItemUuid = $this->route('menu_item.uuid');

            $existingRecord = MenuItem::query()
                ->byTeam()
                ->when($menuItemUuid, function ($q, $menuItemUuid) {
                    $q->where('uuid', '!=', $menuItemUuid);
                })
                ->where(function ($q) {
                    $q->where('name', $this->name);
                })->exists();

            if ($existingRecord) {
                $validator->errors()->add('name', __('validation.unique', ['attribute' => __('mess.menu.props.name')]));
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
            'name' => __('mess.menu.props.name'),
            'description' => __('mess.menu.props.description'),
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
