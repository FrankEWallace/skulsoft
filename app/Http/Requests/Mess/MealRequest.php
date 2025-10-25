<?php

namespace App\Http\Requests\Mess;

use App\Enums\Mess\MealType;
use App\Models\Mess\Meal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class MealRequest extends FormRequest
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
            'type' => ['required', new Enum(MealType::class)],
            'description' => ['nullable', 'min:3', 'max:1000'],
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {

            $mealUuid = $this->route('meal.uuid');

            $existingRecord = Meal::query()
                ->byTeam()
                ->when($mealUuid, function ($q, $mealUuid) {
                    $q->where('uuid', '!=', $mealUuid);
                })
                ->where(function ($q) {
                    $q->where('name', $this->name);
                })->exists();

            if ($existingRecord) {
                $validator->errors()->add('name', __('validation.unique', ['attribute' => __('mess.meal.props.name')]));
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
            'name' => __('mess.meal.props.name'),
            'type' => __('mess.meal.props.type'),
            'description' => __('mess.meal.props.description'),
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
