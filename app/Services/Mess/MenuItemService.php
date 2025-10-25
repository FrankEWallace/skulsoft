<?php

namespace App\Services\Mess;

use App\Models\Mess\MealLogRecord;
use App\Models\Mess\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MenuItemService
{
    public function preRequisite(Request $request)
    {
        return [];
    }

    public function create(Request $request): MenuItem
    {
        \DB::beginTransaction();

        $menuItem = MenuItem::forceCreate($this->formatParams($request));

        \DB::commit();

        return $menuItem;
    }

    private function formatParams(Request $request, ?MenuItem $menuItem = null): array
    {
        $formatted = [
            'name' => $request->name,
            'description' => $request->description,
        ];

        if (! $menuItem) {
            $formatted['team_id'] = auth()->user()?->current_team_id;
        }

        return $formatted;
    }

    public function update(Request $request, MenuItem $menuItem): void
    {
        \DB::beginTransaction();

        $menuItem->forceFill($this->formatParams($request, $menuItem))->save();

        \DB::commit();
    }

    public function deletable(MenuItem $menuItem): void
    {
        $mealLogRecordExists = MealLogRecord::query()
            ->whereMenuItemId($menuItem->id)
            ->exists();

        if ($mealLogRecordExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('mess.menu.item'), 'dependency' => trans('mess.meal.log.log')])]);
        }
    }
}
