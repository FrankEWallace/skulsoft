<?php

namespace App\Actions\Student;

use App\Models\Finance\FeeConcession;
use Illuminate\Support\Arr;

class GetTransportConcessionFeeAmount
{
    public function execute(?FeeConcession $feeConcession = null, float $transportFeeAmount = 0): float
    {
        if (! $feeConcession) {
            return 0;
        }

        if ($transportFeeAmount <= 0) {
            return 0;
        }

        $concessionType = Arr::get($feeConcession->transport, 'type');
        $concessionValue = Arr::get($feeConcession->transport, 'value', 0);

        if ($concessionType == 'amount') {
            return \Price::from($concessionValue)->value;
        }

        return \Price::from($transportFeeAmount * ($concessionValue / 100))->value;
    }
}
