<?php

namespace App\Services\Finance\Report;

use App\Http\Resources\Academic\PeriodResource;
use App\Http\Resources\Finance\LedgerResource;
use App\Http\Resources\Finance\PaymentMethodResource;
use App\Models\Academic\Period;
use App\Models\Finance\Ledger;
use App\Models\Finance\PaymentMethod;

class HeadWiseFeePaymentService
{
    public function preRequisite(): array
    {
        $paymentMethods = PaymentMethodResource::collection(PaymentMethod::query()
            ->byTeam()
            // ->where('is_payment_gateway', false)
            ->get());

        $ledgers = LedgerResource::collection(Ledger::query()
            ->byTeam()
            ->subType('primary')
            ->get()
        );

        $periods = PeriodResource::collection(Period::query()
            ->byTeam()
            ->get());

        return compact('paymentMethods', 'ledgers', 'periods');
    }
}
