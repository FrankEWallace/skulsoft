<?php

namespace App\Actions\Student;

use App\Models\Finance\Transaction;
use App\Models\Student\FeePayment;
use App\Models\Student\FeeRecord;

class PayFeeHead
{
    public function execute(FeeRecord $studentFeeRecord, Transaction $transaction, float $amount = 0): float
    {
        if ($amount <= 0) {
            return $amount;
        }

        $balance = $studentFeeRecord->getBalance()->value;

        // to allow concession amount to be logged
        // if ($balance <= 0 && $concessionAmount <= 0) {
        //     return $amount;
        // }

        $payableAmount = $balance;

        if ($payableAmount > $amount) {
            $payableAmount = $amount;
        }

        $amount -= $payableAmount;

        $studentFeeRecord->paid = $studentFeeRecord->paid->value + $payableAmount;
        $studentFeeRecord->save();

        $concessionAmount = 0;
        if ($studentFeeRecord->amount->value == ($studentFeeRecord->paid->value + $studentFeeRecord->concession->value)) {
            $concessionAmount = $studentFeeRecord->concession->value;

            if ($concessionAmount > 0) {
                $concessionPaid = FeePayment::query()
                    ->where('student_fee_id', $studentFeeRecord->student_fee_id)
                    ->where('fee_head_id', $studentFeeRecord->fee_head_id)
                    ->where('default_fee_head', $studentFeeRecord->default_fee_head?->value)
                    ->whereHas('transaction', function ($q) {
                        $q->where(function ($q) {
                            $q->whereIsOnline(0)->whereNull('cancelled_at')->whereNull('rejected_at');
                        })->orWhere(function ($q) {
                            $q->whereIsOnline(1)->whereNull('processed_at');
                        });
                    })
                    ->sum('concession_amount');

                if ($concessionPaid > 0) {
                    $concessionAmount -= $concessionPaid;
                }
            }
        }

        if ($payableAmount > 0 || $concessionAmount > 0) {
            FeePayment::forceCreate([
                'student_fee_id' => $studentFeeRecord->student_fee_id,
                'fee_head_id' => $studentFeeRecord->fee_head_id,
                'default_fee_head' => $studentFeeRecord->default_fee_head,
                'transaction_id' => $transaction->id,
                'amount' => $payableAmount,
                'concession_amount' => $concessionAmount,
            ]);
        }

        return $amount;
    }
}
