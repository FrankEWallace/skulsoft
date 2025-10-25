<?php

namespace App\Actions\Student;

use App\Enums\Finance\DefaultFeeHead;
use App\Models\Finance\FeeConcession;
use App\Models\Student\Fee as StudentFee;
use App\Models\Student\FeeRecord;
use App\Models\Transport\Circle;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class UpdateFeeInstallment
{
    public function execute(StudentFee $studentFee, ?FeeConcession $feeConcession, ?Circle $transportCircle, array $params = []): void
    {
        if ($studentFee->getMeta('has_custom_concession') && $feeConcession) {
            throw ValidationException::withMessages(['message' => trans('student.fee.could_not_set_concession_for_existing_custom_concession')]);
        }

        $feeInstallment = $studentFee->installment;

        if ($feeInstallment->getMeta('has_no_concession') && $feeConcession) {
            throw ValidationException::withMessages(['message' => trans('student.fee.could_not_set_concession_for_no_concession')]);
        }

        $studentFee->due_date = $feeInstallment->due_date->value != Arr::get($params, 'due_date') ? Arr::get($params, 'due_date') : null;

        $lateFee = [];

        $installmentLateFee = $feeInstallment->late_fee;

        if ((bool) Arr::get($installmentLateFee, 'applicable') != (bool) Arr::get($params, 'late_fee.applicable')) {
            $lateFee['applicable'] = (bool) Arr::get($params, 'late_fee.applicable');
        }

        if (Arr::get($installmentLateFee, 'frequency') != Arr::get($params, 'late_fee.frequency')) {
            $lateFee['frequency'] = Arr::get($params, 'late_fee.frequency');
        }

        if (Arr::get($installmentLateFee, 'type') != Arr::get($params, 'late_fee.type')) {
            $lateFee['type'] = Arr::get($params, 'late_fee.type');
        }

        if (Arr::get($installmentLateFee, 'value') != Arr::get($params, 'late_fee.value')) {
            $lateFee['value'] = (float) Arr::get($params, 'late_fee.value');
        }

        $studentMetaFee = $studentFee->fee;
        $studentMetaFee['late_fee'] = count($lateFee) ? $lateFee : [];
        $studentFee->fee = $studentMetaFee;

        $studentFee->transport_circle_id = $transportCircle?->id;
        $studentFee->transport_direction = $transportCircle ? Arr::get($params, 'direction') : null;

        $studentFee->fee_concession_id = $feeConcession?->id;

        $heads = collect(Arr::get($params, 'heads', []));

        foreach ($heads as $head) {
            $feeInstallmentRecord = $feeInstallment->records->firstWhere('fee_head_id', Arr::get($head, 'id'));

            $studentFeeRecord = $studentFee->records->firstWhere('fee_head_id', Arr::get($head, 'id'));

            // No need to check this as we might create new record if required
            // if (!$feeInstallmentRecord && !$studentFeeRecord) {
            //     throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('finance.fee_structure.installment')])]);
            // }

            if ($studentFeeRecord?->default_fee_head) {
                throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
            }

            $amount = 0;
            $customAmount = Arr::get($head, 'custom_amount', 0);

            $hasCustomAmount = false;

            if ($customAmount != $feeInstallmentRecord->amount->value) {
                $hasCustomAmount = true;
            }

            if ($feeInstallmentRecord->is_optional) {
                if (Arr::get($head, 'is_applicable')) {
                    $amount = $customAmount;
                } else {
                    $amount = 0;
                }
            } else {
                $amount = $customAmount;
            }

            $applicableTo = $feeInstallmentRecord->getMeta('applicable_to', 'all');

            // if applicable to new and student is old
            if ($applicableTo == 'new' && ! Arr::get($params, 'is_new_student')) {
                continue;
            }

            // if applicable to old and student is new
            if ($applicableTo == 'old' && ! Arr::get($params, 'is_old_student')) {
                continue;
            }

            $applicableToGender = $feeInstallmentRecord->getMeta('applicable_to_gender', 'all');

            if ($applicableToGender == 'male' && ! Arr::get($params, 'is_male_student')) {
                continue;
            }

            if ($applicableToGender == 'female' && ! Arr::get($params, 'is_female_student')) {
                continue;
            }

            $concessionAmount = (new CalculateFeeConcession)->execute(
                feeConcession: $feeConcession,
                feeHeadId: Arr::get($head, 'id'),
                amount: $amount
            );

            if ($studentFeeRecord && $studentFeeRecord->paid->value > $amount) {
                throw ValidationException::withMessages(['message' => trans('finance.fee.paid_gt_amount', ['paid' => $studentFeeRecord->paid->formatted, 'amount' => \Price::from($amount)->formatted])]);
            }

            if ($amount <= 0 && $concessionAmount <= 0) {
                if ($studentFeeRecord) {
                    $studentFeeRecord->delete();
                }

                continue;
            }

            if ($hasCustomAmount && $studentFeeRecord && $studentFeeRecord->concession->value > 0 && ($studentFeeRecord->paid->value + $studentFeeRecord->concession->value) == $studentFeeRecord->amount->value) {
                throw ValidationException::withMessages(['message' => trans('finance.fee.paid_concession')]);
            }

            if (! $studentFeeRecord) {
                $studentFeeRecord = FeeRecord::forceCreate([
                    'student_fee_id' => $studentFee->id,
                    'fee_head_id' => Arr::get($head, 'id'),
                    'is_optional' => $feeInstallmentRecord->is_optional,
                    'amount' => $amount,
                    'has_custom_amount' => $hasCustomAmount,
                    'concession' => $concessionAmount,
                ]);
            } else {
                $studentFeeRecord->has_custom_amount = $hasCustomAmount;
                $studentFeeRecord->amount = $amount;
                $studentFeeRecord->concession = $concessionAmount;
                $studentFeeRecord->save();
            }
        }

        if ($transportCircle) {
            $transportFeeAmount = (new GetTransportFeeAmount)->execute(
                studentFee: $studentFee,
                feeInstallment: $feeInstallment
            );

            $transportFeeConcessionAmount = (new GetTransportConcessionFeeAmount)->execute(
                feeConcession: $feeConcession,
                transportFeeAmount: $transportFeeAmount
            );

            $studentTransportFeeRecord = FeeRecord::firstOrCreate([
                'student_fee_id' => $studentFee->id,
                'default_fee_head' => DefaultFeeHead::TRANSPORT_FEE,
            ]);
            $studentTransportFeeRecord->amount = $transportFeeAmount;
            $studentTransportFeeRecord->concession = $transportFeeConcessionAmount;
            $studentTransportFeeRecord->save();
        } else {
            FeeRecord::query()
                ->where('student_fee_id', $studentFee->id)
                ->where('default_fee_head', DefaultFeeHead::TRANSPORT_FEE)
                ->delete();
        }

        $studentFee->load('records');

        $studentFee->total = $studentFee->getInstallmentTotal()->value;
        $studentFee->save();
    }
}
