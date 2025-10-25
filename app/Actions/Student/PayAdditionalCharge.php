<?php

namespace App\Actions\Student;

use App\Enums\Finance\DefaultFeeHead;
use App\Models\Finance\Transaction;
use App\Models\Student\Fee;
use App\Models\Student\FeePayment;

class PayAdditionalCharge
{
    public function execute(Fee $studentFee, Transaction $transaction, float $amount = 0): void
    {
        if ($amount <= 0) {
            return;
        }

        FeePayment::forceCreate([
            'student_fee_id' => $studentFee->id,
            'default_fee_head' => DefaultFeeHead::ADDITIONAL_CHARGE->value,
            'transaction_id' => $transaction->id,
            'amount' => $amount,
        ]);
    }
}
