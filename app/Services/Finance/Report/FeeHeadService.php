<?php

namespace App\Services\Finance\Report;

use App\Enums\Finance\DefaultFeeHead;
use App\Enums\Student\StudentStatus;
use App\Http\Resources\Finance\FeeHeadResource;
use App\Models\Finance\FeeHead;

class FeeHeadService
{
    public function preRequisite(): array
    {
        $statuses = StudentStatus::getOptions();

        $feeHeads = FeeHeadResource::collection(FeeHead::query()
            ->byPeriod()
            ->get());

        $defaultFeeHeads = [
            ['name' => trans('finance.fee.default_fee_heads.transport_fee'), 'uuid' => DefaultFeeHead::TRANSPORT_FEE],
            ['name' => trans('finance.fee.default_fee_heads.late_fee'), 'uuid' => DefaultFeeHead::LATE_FEE],
        ];

        return compact('statuses', 'feeHeads', 'defaultFeeHeads');
    }
}
