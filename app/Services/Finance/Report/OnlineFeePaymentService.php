<?php

namespace App\Services\Finance\Report;

use App\Enums\Finance\TransactionStatus;
use App\Http\Resources\Academic\PeriodResource;
use App\Models\Academic\Period;

class OnlineFeePaymentService
{
    public function preRequisite(): array
    {
        $statuses = TransactionStatus::getOptions();

        $periods = PeriodResource::collection(Period::query()
            ->byTeam()
            ->get());

        return compact('statuses', 'periods');
    }
}
