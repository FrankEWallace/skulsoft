<?php

namespace App\Actions\Student;

use App\Models\Finance\FeeConcession;

class CalculateFeeConcession
{
    public function execute(?FeeConcession $feeConcession = null, int $feeHeadId = 0, float $amount = 0): float
    {
        if (! $feeConcession) {
            return 0;
        }

        if ($amount <= 0) {
            return 0;
        }

        $feeConcessionRecord = $feeConcession->records->firstWhere('fee_head_id', $feeHeadId);

        if (! $feeConcessionRecord) {
            return 0;
        }

        if ($feeConcessionRecord->type == 'percent') {
            return \Price::from($amount * ($feeConcessionRecord->value / 100))->value;
        }

        return \Price::from($feeConcessionRecord->value)->value;
    }
}
