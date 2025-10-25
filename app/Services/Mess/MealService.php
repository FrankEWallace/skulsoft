<?php

namespace App\Services\Mess;

use App\Enums\Mess\MealType;
use App\Models\Mess\Meal;
use App\Models\Mess\MealLog;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MealService
{
    public function preRequisite(Request $request)
    {
        $types = MealType::getOptions();

        return compact('types');
    }

    public function create(Request $request): Meal
    {
        \DB::beginTransaction();

        $meal = Meal::forceCreate($this->formatParams($request));

        \DB::commit();

        return $meal;
    }

    private function formatParams(Request $request, ?Meal $meal = null): array
    {
        $formatted = [
            'name' => $request->name,
            'type' => $request->type,
            'description' => $request->description,
        ];

        if (! $meal) {
            $formatted['team_id'] = auth()->user()?->current_team_id;
        }

        return $formatted;
    }

    public function update(Request $request, Meal $meal): void
    {
        \DB::beginTransaction();

        $meal->forceFill($this->formatParams($request, $meal))->save();

        \DB::commit();
    }

    public function deletable(Meal $meal): void
    {
        $mealLogExists = MealLog::query()
            ->whereMealId($meal->id)
            ->exists();

        if ($mealLogExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('mess.meal.meal'), 'dependency' => trans('mess.meal.log.log')])]);
        }
    }
}
