<?php

namespace App\Actions\Student;

use App\Enums\Finance\DefaultFeeHead;
use App\Models\Finance\Transaction;
use App\Models\Finance\TransactionRecord;
use App\Models\Student\Fee;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class PayFeeInstallment
{
    public function execute(Fee $studentFee, Transaction $transaction, float $amount = 0, array $params = []): float
    {
        if ($amount <= 0) {
            return 0;
        }

        $feeRecordTotal = $studentFee->records->sum('amount.value');
        $calculatedInstallmentFee = $studentFee->total->value - $studentFee->additional_charge->value + $studentFee->additional_discount->value;

        if ($feeRecordTotal != $calculatedInstallmentFee) {
            throw ValidationException::withMessages(['message' => trans('student.fee.installment_fee_mismatch', ['installment' => \Price::from($calculatedInstallmentFee)->formatted, 'records' => \Price::from($feeRecordTotal)->formatted])]);
        }

        $date = $transaction->date->value;

        $customLateFee = (bool) $studentFee->getMeta('custom_late_fee');

        if ($customLateFee) {
            $customLateFeeAmount = $studentFee->getMeta('late_fee_amount') ?? 0;
            $balance = $studentFee->total->value - $studentFee->paid->value + $customLateFeeAmount;
        } else {
            $balance = $studentFee->getBalance($date)->value;
        }

        if ($balance <= 0) {
            return $amount;
        }

        $payableAmount = $balance;

        if ($payableAmount > $amount) {
            $payableAmount = $amount;
        }

        $amount -= $payableAmount;
        $payableInstallmentAmount = $payableAmount;

        $meta = [];

        if (Arr::get($params, 'additional_charges', [])) {
            $meta['additional_charges'] = Arr::get($params, 'additional_charges');
        }

        if (Arr::get($params, 'additional_discounts', [])) {
            $meta['additional_discounts'] = Arr::get($params, 'additional_discounts');
        }

        $additionalCharge = collect(Arr::get($meta, 'additional_charges', []))->sum('amount');
        $additionalDiscount = collect(Arr::get($meta, 'additional_discounts', []))->sum('amount');

        TransactionRecord::forceCreate([
            'transaction_id' => $transaction->id,
            'model_type' => 'StudentFee',
            'model_id' => $studentFee->id,
            'amount' => $payableAmount + $additionalCharge - $additionalDiscount,
            'direction' => 1,
            'meta' => $meta,
        ]);

        $studentFee->load('records');

        foreach ($studentFee->records->where('default_fee_head.value', '!=', DefaultFeeHead::LATE_FEE->value) as $studentFeeRecord) {
            $payableAmount = (new PayFeeHead)->execute($studentFeeRecord, $transaction, $payableAmount);
        }

        $studentFee = (new PayLateFee)->execute($studentFee, $transaction, $payableAmount);

        (new PayAdditionalCharge)->execute($studentFee, $transaction, $additionalCharge);

        (new PayAdditionalDiscount)->execute($studentFee, $transaction, $additionalDiscount);

        $studentFee->due_date = $studentFee->due_date->value ?: $studentFee->installment->due_date->value;
        $studentFee->total = $studentFee->total->value + $additionalCharge - $additionalDiscount;
        $studentFee->paid = $studentFee->paid->value + $payableInstallmentAmount + $additionalCharge - $additionalDiscount;
        $studentFee->additional_charge = $studentFee->additional_charge->value + $additionalCharge;
        $studentFee->additional_discount = $studentFee->additional_discount->value + $additionalDiscount;
        $studentFee->save();

        return $amount;
    }
}
