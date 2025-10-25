<?php

namespace App\Services\Finance\Report;

use App\Http\Resources\Academic\PeriodResource;
use App\Http\Resources\Finance\LedgerResource;
use App\Models\Academic\Period;
use App\Models\Finance\Ledger;

class PaymentMethodWiseFeePaymentService
{
    public function preRequisite(): array
    {
        $ledgers = LedgerResource::collection(Ledger::query()
            ->byTeam()
            ->subType('primary')
            ->get()
        );

        $periods = PeriodResource::collection(Period::query()
            ->byTeam()
            ->get());

        return compact('ledgers', 'periods');
    }
}
