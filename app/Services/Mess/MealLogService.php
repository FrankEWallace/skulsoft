<?php

namespace App\Services\Mess;

use App\Http\Resources\Mess\MealResource;
use App\Http\Resources\Mess\MenuItemResource;
use App\Models\Mess\Meal;
use App\Models\Mess\MealLog;
use App\Models\Mess\MealLogRecord;
use App\Models\Mess\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class MealLogService
{
    public function preRequisite(Request $request)
    {
        $meals = MealResource::collection(Meal::query()
            ->byTeam()
            ->get());

        $menuItems = MenuItemResource::collection(MenuItem::query()
            ->byTeam()
            ->get());

        return compact('meals', 'menuItems');
    }

    public function create(Request $request): MealLog
    {
        \DB::beginTransaction();

        $mealLog = MealLog::forceCreate($this->formatParams($request));

        $this->updateRecords($request, $mealLog);

        \DB::commit();

        return $mealLog;
    }

    private function formatParams(Request $request, ?MealLog $mealLog = null): array
    {
        $formatted = [
            'meal_id' => $request->meal_id,
            'date' => $request->date,
            'description' => $request->description,
            'remarks' => $request->remarks,
        ];

        if (! $mealLog) {
            //
        }

        return $formatted;
    }

    private function updateRecords(Request $request, MealLog $mealLog): void
    {
        $menuItemIds = [];
        foreach ($request->menu_items as $menuItem) {
            $mealLogRecord = MealLogRecord::firstOrCreate([
                'meal_log_id' => $mealLog->id,
                'menu_item_id' => Arr::get($menuItem, 'menu_item_id'),
            ]);

            $menuItemIds[] = Arr::get($menuItem, 'menu_item_id');
        }

        MealLogRecord::query()
            ->whereMealLogId($mealLog->id)
            ->whereNotIn('menu_item_id', $menuItemIds)
            ->delete();
    }

    public function update(Request $request, MealLog $mealLog): void
    {
        \DB::beginTransaction();

        $mealLog->forceFill($this->formatParams($request, $mealLog))->save();

        $this->updateRecords($request, $mealLog);

        \DB::commit();
    }

    public function deletable(MealLog $mealLog): void
    {
        // $mealRecordExists = MealRecord::query()
        //     ->whereMealId($meal->id)
        //     ->exists();

        // if ($mealRecordExists) {
        //     throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('library.book_record.book_record'), 'dependency' => trans('library.book.book')])]);
        // }
    }
}
