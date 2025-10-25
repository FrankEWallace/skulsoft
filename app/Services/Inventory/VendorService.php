<?php

namespace App\Services\Inventory;

use App\Http\Resources\Finance\LedgerTypeResource;
use App\Models\Finance\LedgerType;
use Illuminate\Http\Request;

class VendorService
{
    public function preRequisite(Request $request)
    {
        $ledgerTypes = LedgerTypeResource::collection(LedgerType::query()
            ->byTeam()
            ->get());

        return compact('ledgerTypes');
    }
}
