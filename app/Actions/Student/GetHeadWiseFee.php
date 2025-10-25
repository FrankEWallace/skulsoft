<?php

namespace App\Actions\Student;

use App\Enums\Finance\DefaultFeeHead;
use App\Models\Student\Student;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GetHeadWiseFee
{
    public function execute(Student $student, Collection $fees, Collection $feeRecords, string $date): array
    {
        $feeHeads = $feeRecords
            ->map(function ($feeRecord) {
                return [
                    ...$feeRecord->toArray(),
                    'head' => $feeRecord->fee_head_id ? $feeRecord->head : [
                        'name' => DefaultFeeHead::getLabel($feeRecord->default_fee_head->value),
                    ],
                ];
            })
            ->groupBy('head.name')
            ->transform(function ($feeRecords, $key) {
                $amount = $feeRecords->sum('amount.value');
                $concession = $feeRecords->sum('concession.value');
                $total = $amount - $concession;
                $paid = $feeRecords->sum('paid.value');
                $balance = $total - $paid;

                $firstFeeRecord = $feeRecords->first();
                $uuid = Arr::get($firstFeeRecord, 'head.uuid');

                return [
                    'name' => $key,
                    'uuid' => $uuid,
                    'default_fee_head' => Arr::get($firstFeeRecord, 'default_fee_head'),
                    'amount' => \Price::from($amount),
                    'concession' => \Price::from($concession),
                    'total' => \Price::from($total),
                    'paid' => \Price::from($paid),
                    'balance' => \Price::from($balance),
                ];
            })
            ->filter(function ($feeHead) {
                return $feeHead['amount']->value > 0 || $feeHead['concession']->value > 0;
            })
            ->values()
            ->all();

        $lateFeeAmount = $fees->sum(function ($fee) use ($date) {
            return $fee->calculateLateFeeAmount($date)->value;
        });

        if ($lateFeeAmount > 0) {
            $feeHeads[] = [
                'name' => trans('finance.fee_structure.late_fee'),
                'uuid' => (string) Str::uuid(),
                'amount' => \Price::from($lateFeeAmount),
                'concession' => \Price::from(0),
                'total' => \Price::from($lateFeeAmount),
                'paid' => \Price::from(0),
                'balance' => \Price::from($lateFeeAmount),
            ];
        }

        return $feeHeads;
    }
}
