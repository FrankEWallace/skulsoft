<?php

namespace App\Actions\Student;

use App\Models\Finance\FeeAllocation;
use App\Models\Finance\FeeConcession;
use App\Models\Student\Student;
use App\Models\Transport\Circle as TransportCircle;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class AssignFee
{
    public function execute(Student $student, ?FeeConcession $feeConcession = null, ?TransportCircle $transportCircle = null, ?FeeAllocation $feeAllocation = null, array $params = []): void
    {
        if (! $feeAllocation) {
            $feeAllocation = FeeAllocation::query()
                ->whereBatchId($student->batch_id)
                ->first() ?? FeeAllocation::query()
                ->whereCourseId($student->batch->course_id)
                ->first();

            if (! $feeAllocation) {
                throw ValidationException::withMessages(['message' => trans('finance.fee_structure.not_allocated')]);
            }

            $feeAllocation->load(
                'structure.installments.records',
                'structure.installments.transportFee.records',
            );
        }

        foreach ($feeAllocation->structure->installments as $feeInstallment) {
            (new AssignFeeInstallment)->execute(
                student: $student,
                feeInstallment: $feeInstallment,
                feeConcession: $feeConcession,
                transportCircle: $transportCircle,
                params: [
                    'direction' => Arr::get($params, 'direction'),
                    'opted_fee_heads' => Arr::get($params, 'opted_fee_heads', []),
                    'is_new_student' => Arr::get($params, 'is_new_student', false),
                    'is_male_student' => Arr::get($params, 'is_male_student', false),
                    'is_female_student' => Arr::get($params, 'is_female_student', false),
                ]
            );
        }

        $student->fee_structure_id = $feeAllocation->fee_structure_id;

        if (Arr::get($params, 'fee_allocation_batch')) {
            $student->setMeta([
                'fee_allocation_batch' => Arr::get($params, 'fee_allocation_batch'),
            ]);
        }

        $student->save();
    }
}
