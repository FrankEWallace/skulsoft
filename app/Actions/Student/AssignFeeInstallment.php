<?php

namespace App\Actions\Student;

use App\Enums\Finance\DefaultFeeHead;
use App\Models\Finance\FeeConcession;
use App\Models\Finance\FeeHead;
use App\Models\Finance\FeeInstallment;
use App\Models\Student\Fee as StudentFee;
use App\Models\Student\FeeRecord;
use App\Models\Student\Student;
use App\Models\Transport\Circle as TransportCircle;
use Illuminate\Support\Arr;

class AssignFeeInstallment
{
    public function execute(Student $student, FeeInstallment $feeInstallment, ?FeeConcession $feeConcession, ?TransportCircle $transportCircle, array $params = []): void
    {
        $direction = Arr::get($params, 'direction');
        $optedFeeHeads = Arr::get($params, 'opted_fee_heads', []);
        $isNewStudent = Arr::get($params, 'is_new_student', false);
        $isMaleStudent = Arr::get($params, 'is_male_student', false);
        $isFemaleStudent = Arr::get($params, 'is_female_student', false);

        if ($feeInstallment->getMeta('has_no_concession')) {
            $feeConcession = null;
        }

        $optedFeeHeadIds = count($optedFeeHeads) ? FeeHead::query()
            ->byPeriod($student->period_id)
            ->whereIn('uuid', $optedFeeHeads)
            ->pluck('id')
            ->toArray() : [];

        $studentFee = StudentFee::forceCreate([
            'student_id' => $student->id,
            'fee_installment_id' => $feeInstallment->id,
            'fee_concession_id' => $feeConcession?->id,
            'transport_circle_id' => $feeInstallment->transport_fee_id ? $transportCircle?->id : null,
            'transport_direction' => $feeInstallment->transport_fee_id ? $direction : null,
        ]);

        foreach ($feeInstallment->records as $feeInstallmentRecord) {
            $feeHeadId = $feeInstallmentRecord->fee_head_id;
            $isOptional = $feeInstallmentRecord->is_optional;
            $amount = $feeInstallmentRecord->amount->value;

            $applicableTo = $feeInstallmentRecord->applicable_to ?: 'all';
            $applicableToGender = $feeInstallmentRecord->applicable_to_gender ?: 'all';

            $amount = match ($applicableTo) {
                'all' => $amount,
                'new' => $isNewStudent ? $amount : 0,
                'old' => ! $isNewStudent ? $amount : 0,
                default => 0,
            };

            $amount = match ($applicableToGender) {
                'all' => $amount,
                'male' => $isMaleStudent ? $amount : 0,
                'female' => $isFemaleStudent ? $amount : 0,
                default => 0,
            };

            if ($isOptional && ! in_array($feeHeadId, $optedFeeHeadIds)) {
                $amount = 0;
            }

            $concessionAmount = (new CalculateFeeConcession)->execute(
                feeConcession: $feeConcession,
                feeHeadId: $feeHeadId,
                amount: $amount
            );

            if ($amount > 0 || $concessionAmount > 0) {
                FeeRecord::forceCreate([
                    'student_fee_id' => $studentFee->id,
                    'fee_head_id' => $feeHeadId,
                    'amount' => $amount,
                    'is_optional' => $isOptional,
                    'concession' => $concessionAmount,
                ]);
            }
        }

        if ($feeInstallment->transport_fee_id) {
            $transportFeeAmount = (new GetTransportFeeAmount)->execute(
                studentFee: $studentFee,
                feeInstallment: $feeInstallment
            );

            $transportFeeConcessionAmount = (new GetTransportConcessionFeeAmount)->execute(
                feeConcession: $feeConcession,
                transportFeeAmount: $transportFeeAmount
            );

            FeeRecord::forceCreate([
                'student_fee_id' => $studentFee->id,
                'default_fee_head' => DefaultFeeHead::TRANSPORT_FEE,
                'amount' => $transportFeeAmount,
                'concession' => $transportFeeConcessionAmount,
            ]);

            $studentFee->transport_circle_id = $transportCircle?->id;
            $studentFee->transport_direction = $direction;
        }

        $studentFee->load('records');

        $studentFee->total = $studentFee->getInstallmentTotal()->value;
        $studentFee->save();
    }
}
