<?php

namespace App\Services\Finance\Report;

use App\Enums\Finance\TransactionStatus;
use App\Http\Resources\Academic\PeriodResource;
use App\Http\Resources\Finance\PaymentMethodResource;
use App\Domain\Academic\Models\Period;
use App\Models\Finance\PaymentMethod;

class FeePaymentService
{
    public function preRequisite(): array
    {
        $statuses = TransactionStatus::getOptions();

        $paymentMethods = PaymentMethodResource::collection(PaymentMethod::query()
            ->byTeam()
            // ->where('is_payment_gateway', false)
            ->get());

        $periods = PeriodResource::collection(Period::query()
            ->byTeam()
            ->get());

        return compact('statuses', 'paymentMethods', 'periods');
    }
}
