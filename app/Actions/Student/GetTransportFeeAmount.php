<?php

namespace App\Actions\Student;

use App\Models\Finance\FeeInstallment;
use App\Models\Student\Fee;

class GetTransportFeeAmount
{
    public function execute(Fee $studentFee, ?FeeInstallment $feeInstallment = null): float
    {
        if (! $studentFee->transport_circle_id) {
            return 0;
        }

        $feeInstallment ??= $studentFee->installment;

        $transportFee = $feeInstallment?->transportFee?->records?->firstWhere('transport_circle_id', $studentFee->transport_circle_id);

        if (! $transportFee) {
            return 0;
        }

        $directionColumn = $studentFee->transport_direction.'_amount';

        return $transportFee->$directionColumn->value ?? 0;
    }
}
