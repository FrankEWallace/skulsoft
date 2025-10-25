<?php

namespace App\Actions\Student;

use App\Enums\Finance\LateFeeFrequency;
use App\Enums\Finance\PaymentStatus;
use App\Models\Finance\FeeInstallment;
use App\Models\Student\Student;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class GetFeeInstallments
{
    public function execute(Student $student, Collection $fees): array
    {
        if (! $student->fee_structure_id) {
            return [];
        }

        $date = today()->toDateString();

        $feeInstallments = FeeInstallment::query()
            ->with('records.head', 'group')
            ->whereFeeStructureId($student->fee_structure_id)
            ->get();

        $studentFees = [];
        foreach ($feeInstallments as $feeInstallment) {
            $studentFee = $fees->where('installment.id', $feeInstallment->id)->first();

            $transportCircle = [];
            if ($feeInstallment->transport_fee_id) {
                $transportCircle = [
                    'name' => $studentFee?->transportCircle?->name,
                    'uuid' => $studentFee?->transportCircle?->uuid,
                ];
            }

            $concession = [];

            if ($studentFee?->fee_concession_id) {
                $concession = [
                    'uuid' => $studentFee?->concession?->uuid,
                    'name' => $studentFee?->concession?->name,
                ];
            }

            $lateFeeValue = \Percent::from(0);
            if ($studentFee) {
                $lateFeeValue = $studentFee->getLateFee('type', 'amount') === 'amount' ? \Price::from($studentFee->getLateFee('value', 0)) : \Percent::from($studentFee->getLateFee('value', 0));
            }

            $installmentLateFeeValue = Arr::get($feeInstallment->late_fee, 'type', 'amount') == 'amount'
            ? \Price::from(Arr::get($feeInstallment->late_fee, 'value'))
            : \Percent::from(Arr::get($feeInstallment->late_fee, 'value'));

            $records = [];

            foreach ($feeInstallment->records as $record) {
                $studentFeeRecord = $studentFee?->records?->firstWhere('fee_head_id', $record->fee_head_id);

                $isOptional = $record->is_optional;
                $isApplicable = false;

                $applicableTo = $record->getMeta('applicable_to', 'all');

                if ($isOptional && $studentFeeRecord) {
                    $isApplicable = $studentFeeRecord->amount->value > 0 ? true : false;
                }

                // if applicable to new and student is old
                if ($applicableTo == 'new' && $student->joining_date != $student->start_date->value) {
                    continue;
                }

                // if applicable to old and student is new
                if ($applicableTo == 'old' && $student->joining_date == $student->start_date->value) {
                    continue;
                }

                $records[] = [
                    'head' => [
                        'uuid' => $record->head->uuid,
                        'name' => $record->head->name,
                    ],
                    'amount' => \Price::from($studentFeeRecord?->amount?->value ?? $record->amount->value),
                    'paid' => $studentFeeRecord?->paid ?? \Price::from(0),
                    'concession' => $studentFeeRecord?->concession ?? \Price::from(0),
                    'installment_amount' => $record->amount,
                    'is_optional' => $record->is_optional,
                    'is_applicable' => $isApplicable,
                ];
            }

            $studentFees[] = [
                'fee_group_uuid' => $feeInstallment->group->uuid,
                'has_transport_fee' => $feeInstallment->transport_fee_id ? true : false,
                'title' => $feeInstallment->title,
                'has_no_concession' => (bool) $feeInstallment->getMeta('has_no_concession'),
                'uuid' => $studentFee->uuid ?? $feeInstallment->uuid,
                'direction' => $studentFee?->transport_direction,
                'transport_circle' => $transportCircle,
                'concession' => $concession,
                'status' => PaymentStatus::getDetail($studentFee?->getStatus() ?? PaymentStatus::UNPAID),
                'due_date' => $studentFee?->getDueDate() ?? $feeInstallment->due_date,
                'overdue' => $studentFee?->getOverdueDays() ?? 0,
                'late_fee' => $studentFee ? [
                    'applicable' => $studentFee->getLateFee('applicable', false),
                    'type' => $studentFee->getLateFee('type', 'amount'),
                    'frequency' => LateFeeFrequency::getDetail($studentFee->getLateFee('frequency')),
                    'value' => $lateFeeValue,
                    'amount' => $studentFee->calculateLateFeeAmount($date),
                    'paid' => \Price::from($studentFee->getLateFee('paid', 0)),
                ] : [
                    'applicable' => (bool) Arr::get($feeInstallment->late_fee, 'applicable', false),
                    'type' => Arr::get($feeInstallment->late_fee, 'type', 'amount'),
                    'frequency' => LateFeeFrequency::getDetail(Arr::get($feeInstallment->late_fee, 'frequency')),
                    'value' => $installmentLateFeeValue,
                    'amount' => \Price::from(0),
                    'paid' => \Price::from(0),
                ],
                'records' => $records,
            ];
        }

        return $studentFees;
    }
}
