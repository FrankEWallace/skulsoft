<?php

namespace App\Services\Inventory\Report;

use App\Http\Resources\Inventory\InventoryResource;
use App\Models\Inventory\Inventory;

class ItemSummaryService
{
    public function preRequisite(): array
    {
        $inventories = InventoryResource::collection(Inventory::query()
            ->byTeam()
            ->get());

        return compact("inventories");
    }
}
