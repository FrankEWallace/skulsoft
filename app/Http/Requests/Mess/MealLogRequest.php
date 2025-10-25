<?php

namespace App\Http\Requests\Mess;

use App\Models\Mess\Meal;
use App\Models\Mess\MealLog;
use App\Models\Mess\MenuItem;
use Illuminate\Foundation\Http\FormRequest;

class MealLogRequest extends FormRequest
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
            'meal' => ['required', 'uuid'],
            'date' => ['required', 'date_format:Y-m-d'],
            'menu_items' => ['required', 'array', 'min:1'],
            'description' => ['nullable', 'max:255'],
            'remarks' => ['nullable', 'min:3', 'max:1000'],
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {

            $mealLogUuid = $this->route('meal_log.uuid');

            $meal = Meal::query()
                ->byTeam()
                ->where('uuid', $this->meal)
                ->getOrFail(trans('mess.meal.meal'), 'meal');

            $existingRecord = MealLog::query()
                ->where('meal_id', $meal->id)
                ->where('date', $this->date)
                ->where('description', $this->description)
                ->when($mealLogUuid, function ($q, $mealLogUuid) {
                    $q->where('uuid', '!=', $mealLogUuid);
                })
                ->exists();

            if ($existingRecord) {
                $validator->errors()->add('date', __('validation.unique', ['attribute' => __('mess.meal.log.log')]));
            }

            $menuItems = MenuItem::query()
                ->byTeam()
                ->select('id', 'uuid')
                ->get();

            $newMenuItems = [];
            foreach ($this->menu_items as $menuItem) {
                $menuItem = $menuItems->firstWhere('uuid', $menuItem);
                if (! $menuItem) {
                    $validator->errors()->add('menu_items', __('validation.exists', ['attribute' => __('mess.menu.item')]));
                }

                $newMenuItems[] = ['menu_item_id' => $menuItem?->id];
            }

            $this->merge([
                'meal_id' => $meal->id,
                'menu_items' => $newMenuItems,
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
            'meal' => __('mess.meal.meal'),
            'menu_items' => __('mess.menu.item'),
            'date' => __('mess.meal.log.props.date'),
            'description' => __('mess.meal.log.props.description'),
            'remarks' => __('mess.meal.log.props.remarks'),
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
