<?php

namespace App\Services\Inventory;

use App\Models\Inventory\Inventory;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function preRequisite(Request $request)
    {
        return [];
    }

    public function create(Request $request): Inventory
    {
        \DB::beginTransaction();

        $inventory = Inventory::forceCreate($this->formatParams($request));

        \DB::commit();

        return $inventory;
    }

    private function formatParams(Request $request, ?Inventory $inventory = null): array
    {
        $formatted = [
            'name' => $request->name,
            'description' => $request->description,
        ];

        if (! $inventory) {
            $formatted['team_id'] = auth()->user()?->current_team_id;
        }

        return $formatted;
    }

    public function update(Request $request, Inventory $inventory): void
    {
        \DB::beginTransaction();

        $inventory->forceFill($this->formatParams($request, $inventory))->save();

        \DB::commit();
    }

    public function deletable(Inventory $inventory): bool
    {
        $stockCategoryExists = \DB::table('stock_categories')
            ->whereInventoryId($inventory->id)
            ->exists();

        if ($stockCategoryExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('inventory.inventory'), 'dependency' => trans('inventory.stock_category.stock_category')])]);
        }

        return true;
    }
}
