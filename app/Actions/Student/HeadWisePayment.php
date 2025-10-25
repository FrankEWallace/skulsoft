<?php

namespace App\Actions\Student;

use App\Actions\Finance\CreateTransaction;
use App\Enums\Finance\DefaultFeeHead;
use App\Enums\Finance\TransactionType;
use App\Models\Finance\TransactionRecord;
use App\Models\Student\Fee;
use App\Models\Student\FeePayment;
use App\Models\Student\FeeRecord;
use App\Models\Student\Student;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class HeadWisePayment
{
    public function execute(Student $student, array $params = []): array
    {
        $fees = Fee::query()
            ->select('student_fees.*', \DB::raw('COALESCE(student_fees.due_date, fee_installments.due_date) as final_due_date'))
            ->join('fee_installments', 'fee_installments.id', '=', 'student_fees.fee_installment_id')
            ->whereStudentId($student->id)
            ->where('fee_installments.fee_group_id', Arr::get($params, 'fee_group_id'))
            ->orderBy('final_due_date', 'asc')
            ->get();

        $feeRecords = FeeRecord::query()
            ->with('head')
            ->whereIn('student_fee_id', $fees->pluck('id')->all())
            ->get();

        $feeHeads = (new GetHeadWiseFee)->execute(
            student: $student,
            fees: $fees,
            feeRecords: $feeRecords,
            date: Arr::get($params, 'date'),
        );

        $firstStudentFeeId = null;
        $payableStudentFeeRecords = [];
        foreach (Arr::get($params, 'heads', []) as $index => $inputFeeHead) {
            $feeHead = collect($feeHeads)->firstWhere('uuid', $inputFeeHead['uuid']);

            $feeHeadBalance = $feeHead['balance']?->value ?? 0;

            if ($feeHeadBalance < $inputFeeHead['amount']) {
                return [
                    'status' => false,
                    'balance' => $feeHeadBalance,
                    'amount' => Arr::get($inputFeeHead, 'amount', 0),
                    'head' => Arr::get($feeHead, 'name') ?? Arr::get($inputFeeHead, 'name'),
                    'voucher_no' => Arr::get($params, 'code_number'),
                    'key' => 'heads.'.$index.'.amount',
                    'error' => 'Excess Payment',
                    'message' => trans('student.fee.could_not_make_excess_payment', ['attribute' => \Price::from($feeHeadBalance)->formatted]),
                ];
            }

            $amount = $inputFeeHead['amount'];

            foreach ($fees as $studentFee) {
                $firstStudentFeeId = $firstStudentFeeId ?? $studentFee->id;
                // $studentFeeRecord = $feeRecords->where('student_fee_id', $studentFee->id)->firstWhere('fee_head_id', $inputFeeHead['id']);
                $studentFeeRecords = $feeRecords->where('student_fee_id', $studentFee->id)->where('fee_head_id', $inputFeeHead['id']);

                foreach ($studentFeeRecords as $studentFeeRecord) {
                    if ($studentFeeRecord && $amount > 0) {
                        $balance = $studentFeeRecord->getBalance()->value;

                        if ($amount > $balance) {
                            $amount -= $balance;

                            $payableStudentFeeRecords[] = [
                                'student_fee_record_id' => $studentFeeRecord->id,
                                'student_fee_id' => $studentFee->id,
                                'due_date' => $studentFee->final_due_date,
                                'fee_head_id' => $studentFeeRecord->fee_head_id,
                                'fee_head_name' => $feeHead['name'],
                                'id' => $studentFeeRecord->id,
                                'amount' => $balance,
                            ];
                        } else {
                            $payableStudentFeeRecords[] = [
                                'student_fee_record_id' => $studentFeeRecord->id,
                                'student_fee_id' => $studentFee->id,
                                'due_date' => $studentFee->final_due_date,
                                'fee_head_id' => $studentFeeRecord->fee_head_id,
                                'fee_head_name' => $feeHead['name'],
                                'id' => $studentFeeRecord->id,
                                'amount' => $amount,
                            ];
                            $amount = 0;
                        }
                    }
                }
            }
        }

        if (Arr::get($params, 'transport_fee', 0)) {
            $transportFeeRecords = $feeRecords->where('default_fee_head.value', 'transport_fee');

            $transportFeeRecordBalance = $transportFeeRecords->sum(function ($transportFeeRecord) {
                return $transportFeeRecord->getBalance()->value;
            });

            if ($transportFeeRecordBalance < Arr::get($params, 'transport_fee', 0)) {
                return [
                    'status' => false,
                    'balance' => $transportFeeRecordBalance,
                    'amount' => Arr::get($params, 'transport_fee', 0),
                    'head' => 'Transport Fee',
                    'voucher_no' => Arr::get($params, 'code_number'),
                    'key' => 'transport_fee',
                    'error' => 'Excess Payment',
                    'message' => trans('student.fee.could_not_make_excess_payment', ['attribute' => \Price::from($transportFeeRecordBalance)->formatted]),
                ];
            }

            $transportFeeAmount = Arr::get($params, 'transport_fee', 0);
            foreach ($transportFeeRecords as $transportFeeRecord) {
                if (empty($firstStudentFeeId)) {
                    $firstStudentFeeId = $transportFeeRecord->student_fee_id;
                }

                if ($transportFeeAmount > 0) {
                    $transportFeeBalance = $transportFeeRecord->getBalance()->value;

                    if ($transportFeeAmount > $transportFeeBalance) {
                        $transportFeeAmount -= $transportFeeBalance;

                        $payableStudentFeeRecords[] = [
                            'student_fee_record_id' => $transportFeeRecord->id,
                            'student_fee_id' => $transportFeeRecord->student_fee_id,
                            'fee_head_id' => null,
                            'default_fee_head' => 'transport_fee',
                            'fee_head_name' => 'Transport Fee',
                            'id' => $transportFeeRecord->id,
                            'amount' => $transportFeeBalance,
                        ];
                    } else {
                        $payableStudentFeeRecords[] = [
                            'student_fee_record_id' => $transportFeeRecord->id,
                            'student_fee_id' => $transportFeeRecord->student_fee_id,
                            'fee_head_id' => null,
                            'default_fee_head' => 'transport_fee',
                            'fee_head_name' => 'Transport Fee',
                            'id' => $transportFeeRecord->id,
                            'amount' => $transportFeeAmount,
                        ];
                        $transportFeeAmount = 0;
                    }
                }
            }
        }

        if (Arr::get($params, 'late_fee', 0)) {
            $payableStudentFeeRecords[] = [
                'student_fee_id' => $firstStudentFeeId,
                'due_date' => today()->toDateString(),
                'fee_head_id' => null,
                'default_fee_head' => 'late_fee',
                'fee_head_name' => 'Late Fee',
                'id' => null,
                'amount' => Arr::get($params, 'late_fee', 0),
            ];

            if (empty($firstStudentFeeId)) {
                return [
                    'status' => false,
                    'voucher_no' => Arr::get($params, 'code_number'),
                    'key' => 'message',
                    'error' => 'No installment found',
                    'message' => trans('student.fee.could_not_apply_late_fee_without_installment'),
                ];
            }
        }

        $totalAdditionalCharge = array_sum(array_column(Arr::get($params, 'additional_charges', []), 'amount'));
        $totalAdditionalDiscount = array_sum(array_column(Arr::get($params, 'additional_discounts', []), 'amount'));

        $totalPayableFeeHeadAmount = collect($payableStudentFeeRecords)->sum('amount');

        $totalPayableFeeHeadAmount += ($totalAdditionalCharge - $totalAdditionalDiscount);

        if ($totalPayableFeeHeadAmount != Arr::get($params, 'amount')) {
            throw ValidationException::withMessages(['message' => trans('student.fee.total_head_amount_mismatch', ['total' => \Price::from($totalPayableFeeHeadAmount)->formatted, 'input' => \Price::from(Arr::get($params, 'amount'))->formatted])]);
        }

        if (($totalAdditionalCharge || $totalAdditionalDiscount) && empty($firstStudentFeeId)) {
            return [
                'status' => false,
                'voucher_no' => Arr::get($params, 'code_number'),
                'key' => 'message',
                'error' => 'No installment found',
                'message' => trans('student.fee.could_not_apply_additional_fee_without_installment'),
            ];
        }

        if ($totalAdditionalCharge) {
            $payableStudentFeeRecords[] = [
                'student_fee_id' => $firstStudentFeeId,
                'due_date' => today()->toDateString(),
                'fee_head_id' => null,
                'default_fee_head' => 'additional_charge',
                'fee_head_name' => 'Additional Charge',
                'id' => null,
                'amount' => $totalAdditionalCharge,
            ];
        }

        if ($totalAdditionalDiscount) {
            $payableStudentFeeRecords[] = [
                'student_fee_id' => $firstStudentFeeId,
                'due_date' => today()->toDateString(),
                'fee_head_id' => null,
                'default_fee_head' => 'additional_discount',
                'fee_head_name' => 'Additional Discount',
                'id' => null,
                'amount' => $totalAdditionalDiscount,
            ];
        }

        $payableStudentFees = collect($payableStudentFeeRecords)->groupBy('student_fee_id')->toArray();

        if (! count($payableStudentFees)) {
            throw ValidationException::withMessages(['message' => trans('student.fee.no_payable_fee')]);
        }

        $params['period_id'] = $student->period_id;
        $params['transactionable_type'] = 'Student';
        $params['transactionable_id'] = $student->id;
        $params['head'] = 'student_fee';
        $params['type'] = TransactionType::RECEIPT->value;

        $params['batch_id'] = $student->batch_id;
        $params['payments'] = [
            [
                'ledger_id' => Arr::get($params, 'ledger_id'),
                'amount' => Arr::get($params, 'amount', 0),
                'payment_method_id' => Arr::get($params, 'payment_method_id'),
                'payment_method_details' => [],
            ],
        ];

        \DB::beginTransaction();

        $transaction = (new CreateTransaction)->execute($params);

        // $payableAmount = $transaction->amount->value;
        $payableAmount = $transaction->amount->value + $totalAdditionalDiscount - $totalAdditionalCharge;

        $i = 0;
        foreach ($payableStudentFees as $studentFeeId => $payableStudentFee) {

            $installmentAmount = collect($payableStudentFee)->sum(function ($payableStudentFeeRecord) {
                $sign = 1;
                if (Arr::get($payableStudentFeeRecord, 'default_fee_head') == 'additional_discount') {
                    $sign = -1;
                }

                return Arr::get($payableStudentFeeRecord, 'amount') * $sign;
            });

            $studentFee = Fee::query()
                ->where('student_id', $student->id)
                ->where('id', $studentFeeId)
                ->first();

            $transactionRecordMeta = [];
            if ($i == 0 && ($totalAdditionalCharge > 0 || $totalAdditionalDiscount > 0)) {
                if (Arr::get($params, 'additional_charges', [])) {
                    $transactionRecordMeta['additional_charges'] = Arr::get($params, 'additional_charges');
                }

                if (Arr::get($params, 'additional_discounts', [])) {
                    $transactionRecordMeta['additional_discounts'] = Arr::get($params, 'additional_discounts');
                }

                $studentFee->total = $studentFee->total->value + $totalAdditionalCharge - $totalAdditionalDiscount;
                $studentFee->paid = $studentFee->paid->value + $installmentAmount;
                $studentFee->additional_charge = $studentFee->additional_charge->value + $totalAdditionalCharge;
                $studentFee->additional_discount = $studentFee->additional_discount->value + $totalAdditionalDiscount;
            } else {
                $studentFee->paid = $studentFee->paid->value + $installmentAmount;
            }

            $studentFee->save();

            if ($installmentAmount != 0) {
                TransactionRecord::forceCreate([
                    'transaction_id' => $transaction->id,
                    'model_type' => 'StudentFee',
                    'model_id' => $studentFeeId,
                    'amount' => $installmentAmount,
                    'direction' => 1,
                    'meta' => $transactionRecordMeta,
                ]);
            }

            foreach ($payableStudentFee as $payableStudentFeeRecord) {
                $feeHeadAmount = Arr::get($payableStudentFeeRecord, 'amount');

                $feeRecordId = Arr::get($payableStudentFeeRecord, 'student_fee_record_id');
                $feeHeadId = Arr::get($payableStudentFeeRecord, 'fee_head_id');
                $feeHeadName = Arr::get($payableStudentFeeRecord, 'fee_head_name');
                $defaultFeeHead = Arr::get($payableStudentFeeRecord, 'default_fee_head');
                $concessionAmount = 0;

                if ($feeHeadId) {
                    FeeRecord::query()
                        ->whereId($feeRecordId)
                        ->where('student_fee_id', $studentFeeId)
                        ->where('fee_head_id', $feeHeadId)
                        ->update([
                            'paid' => \DB::raw('paid + '.$feeHeadAmount),
                        ]);

                    $feeRecord = FeeRecord::whereId($feeRecordId)->first();
                } elseif ($defaultFeeHead == 'transport_fee') {
                    FeeRecord::query()
                        ->where('student_fee_id', $studentFeeId)
                        ->where('default_fee_head', 'transport_fee')
                        ->update([
                            'paid' => \DB::raw('paid + '.$feeHeadAmount),
                        ]);

                    $feeRecord = FeeRecord::where('student_fee_id', $studentFeeId)->where('default_fee_head', 'transport_fee')->first();
                } elseif ($defaultFeeHead == 'late_fee') {
                    $lateFeeRecord = FeeRecord::firstOrCreate([
                        'student_fee_id' => $studentFeeId,
                        'default_fee_head' => DefaultFeeHead::LATE_FEE,
                    ]);

                    $lateFeeRecord->amount = $lateFeeRecord->amount->value + $feeHeadAmount;
                    $lateFeeRecord->paid = $lateFeeRecord->paid->value + $feeHeadAmount;
                    $lateFeeRecord->save();

                    $feeRecord = $lateFeeRecord;

                    Fee::query()
                        ->whereId($studentFeeId)
                        ->update([
                            'total' => \DB::raw('total + '.$feeHeadAmount),
                        ]);
                }

                if ($feeRecord->amount->value == ($feeRecord->paid->value + $feeRecord->concession->value)) {
                    $concessionAmount = $feeRecord->concession->value;
                }

                if ($feeHeadAmount > 0) {
                    FeePayment::forceCreate([
                        'student_fee_id' => $studentFeeId,
                        'fee_head_id' => $feeHeadId,
                        'default_fee_head' => $defaultFeeHead,
                        'transaction_id' => $transaction->id,
                        'amount' => $feeHeadAmount,
                        'concession_amount' => $concessionAmount,
                    ]);
                }
            }

            $i++;
        }

        \DB::commit();

        return ['status' => true];
    }
}
