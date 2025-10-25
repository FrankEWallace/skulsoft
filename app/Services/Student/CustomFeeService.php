<?php

namespace App\Services\Student;

use App\Actions\Student\CreateCustomFeeHead;
use App\Concerns\StudentAction;
use App\Http\Resources\Finance\FeeHeadResource;
use App\Models\Finance\FeeHead;
use App\Models\Student\Fee;
use App\Models\Student\FeeRecord;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CustomFeeService
{
    use StudentAction;

    public function preRequisite(Request $request): array
    {
        $feeHeads = FeeHeadResource::collection(FeeHead::query()
            ->byPeriod()
            ->whereHas('group', function ($q) {
                $q->where('meta->is_custom', true);
            })
            ->get());

        return compact('feeHeads');
    }

    public function findByUuidOrFail(Student $student, string $uuid): FeeRecord
    {
        return FeeRecord::query()
            ->whereHas('fee', function ($q) use ($student) {
                $q->where('student_id', $student->id)
                    ->whereHas('installment', function ($q) {
                        $q->where('meta->is_custom', true);
                    });
            })
            ->whereUuid($uuid)
            ->getOrFail(trans('student.fee.custom_fee'));
    }

    public function create(Request $request, Student $student): FeeRecord
    {
        $this->ensureIsNotTransferred($student);

        $feeRecord = (new CreateCustomFeeHead)->execute($student, $request->all());

        return $feeRecord;
    }

    private function formatParams(Request $request): array
    {
        $formatted = [
            'fee_head_id' => $request->fee_head_id,
            'amount' => $request->amount,
            'due_date' => $request->due_date,
            'remarks' => $request->remarks,
        ];

        return $formatted;
    }

    private function ensureHasForceCustomFeePermission(FeeRecord $feeRecord): void
    {
        if (! $feeRecord->getMeta('is_force_set')) {
            return;
        }

        if (! auth()->user()->can('fee:manage-force-custom-fee')) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }
    }

    public function update(Request $request, Student $student, FeeRecord $feeRecord): void
    {
        $this->ensureIsNotTransferred($student);

        $this->ensureHasForceCustomFeePermission($feeRecord);

        if ($feeRecord->paid->value > $request->amount) {
            throw ValidationException::withMessages(['message' => trans('student.fee.could_not_edit_if_fee_paid')]);
        }

        $difference = $request->amount - $feeRecord->amount->value;

        \DB::beginTransaction();

        $feeRecord->forceFill($this->formatParams($request))->save();

        if ($difference) {
            $studentFee = Fee::query()
                ->where('student_id', $student->id)
                ->whereId($feeRecord->student_fee_id)
                ->first();

            $studentFee->total = $studentFee->total->value + $difference;
            $studentFee->save();
        }

        \DB::commit();
    }

    public function deletable(Student $student, FeeRecord $feeRecord): void
    {
        $this->ensureIsNotTransferred($student);

        if ($feeRecord->paid->value) {
            throw ValidationException::withMessages(['message' => trans('student.fee.could_not_edit_if_fee_paid')]);
        }
    }

    public function delete(Student $student, FeeRecord $feeRecord): void
    {
        $this->ensureHasForceCustomFeePermission($feeRecord);

        \DB::beginTransaction();

        $studentFee = Fee::query()
            ->where('student_id', $student->id)
            ->whereId($feeRecord->student_fee_id)
            ->first();

        $feeRecord->delete();

        $studentFee->total = FeeRecord::query()
            ->where('student_fee_id', $studentFee->id)
            ->sum('amount');
        $studentFee->save();

        \DB::commit();
    }
}
