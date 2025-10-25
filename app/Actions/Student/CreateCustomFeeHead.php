<?php

namespace App\Actions\Student;

use App\Actions\Finance\CreateCustomFeeInstallment;
use App\Models\Student\Fee;
use App\Models\Student\FeeRecord;
use App\Models\Student\Student;

class CreateCustomFeeHead
{
    public function execute(Student $student, array $params = [])
    {
        $feeInstallment = (new CreateCustomFeeInstallment)->execute($student->fee_structure_id);

        $studentFee = Fee::query()
            ->where('student_id', $student->id)
            ->where('fee_installment_id', $feeInstallment->id)
            ->first();

        \DB::beginTransaction();

        if (! $studentFee) {
            $studentFee = Fee::forceCreate([
                'student_id' => $student->id,
                'fee_installment_id' => $feeInstallment->id,
            ]);
        }

        $data['fee_head_id'] = $params['fee_head_id'];
        $data['amount'] = $params['amount'];
        $data['due_date'] = $params['due_date'];
        $data['remarks'] = $params['remarks'];
        $data['student_fee_id'] = $studentFee->id;
        $data['meta'] = $params['meta'] ?? null;

        $feeRecord = FeeRecord::forceCreate($data);

        $studentFee->total = FeeRecord::query()
            ->where('student_fee_id', $studentFee->id)
            ->sum('amount');
        $studentFee->save();

        \DB::commit();

        return $feeRecord;
    }
}
