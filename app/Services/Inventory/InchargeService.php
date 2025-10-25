<?php

namespace App\Services\Inventory;

use App\Http\Resources\Inventory\InventoryResource;
use App\Models\Incharge;
use App\Models\Inventory\Inventory;
use Illuminate\Http\Request;

class InchargeService
{
    public function preRequisite(Request $request)
    {
        $inventories = InventoryResource::collection(Inventory::query()
            ->byTeam()
            ->get());

        return compact('inventories');
    }

    public function create(Request $request): Incharge
    {
        \DB::beginTransaction();

        $incharge = Incharge::forceCreate($this->formatParams($request));

        \DB::commit();

        return $incharge;
    }

    private function formatParams(Request $request, ?Incharge $incharge = null): array
    {
        $formatted = [
            'model_type' => 'Inventory',
            'model_id' => $request->inventory_id,
            'employee_id' => $request->employee_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'remarks' => $request->remarks,
        ];

        if (! $incharge) {
            //
        }

        return $formatted;
    }

    public function update(Request $request, Incharge $incharge): void
    {
        \DB::beginTransaction();

        $incharge->forceFill($this->formatParams($request, $incharge))->save();

        \DB::commit();
    }

    public function deletable(Incharge $incharge): void
    {
        //
    }
}
