<?php

namespace App\Services\Finance;

use App\Actions\Finance\CreateFeeInstallment;
use App\Actions\Student\AssignFeeInstallment;
use App\Enums\Gender;
use App\Models\Finance\FeeConcession;
use App\Models\Finance\FeeGroup;
use App\Models\Finance\FeeInstallment;
use App\Models\Finance\FeeStructure;
use App\Models\Student\Fee;
use App\Models\Student\Student;
use App\Models\Transport\Circle;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FeeInstallmentService
{
    public function create(FeeStructure $feeStructure, Request $request): void
    {
        $feeGroup = FeeGroup::query()
            ->byPeriod()
            ->whereId($request->fee_group_id)
            ->firstOrFail();

        if ($feeGroup->getMeta('is_custom')) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        \DB::beginTransaction();

        $params = $request->all();
        $params['action'] = 'create';

        $feeInstallment = (new CreateFeeInstallment)->execute(feeStructure: $feeStructure, params: $params);

        $feeInstallment->refresh();
        $feeInstallment->load('records');

        $students = Student::query()
            ->select('students.*', 'contacts.gender', 'admissions.joining_date')
            ->join('contacts', 'students.contact_id', '=', 'contacts.id')
            ->join('admissions', 'students.admission_id', '=', 'admissions.id')
            ->whereFeeStructureId($feeStructure->id)
            ->get();

        $feeConcessions = FeeConcession::query()
            ->with('records')
            ->byPeriod($feeStructure->period_id)
            ->get();

        $transportCircles = Circle::query()
            ->byPeriod($feeStructure->period_id)
            ->get();

        foreach ($students as $student) {
            $studentFee = Fee::query()
                ->whereStudentId($student->id)
                ->whereHas('installment', function ($q) use ($feeInstallment) {
                    $q->where('fee_group_id', $feeInstallment->fee_group_id);
                })
                ->orderBy('id', 'desc')
                ->first();

            $feeConcession = $feeConcessions->firstWhere('id', $studentFee?->fee_concession_id);
            $transportCircle = $transportCircles->firstWhere('id', $studentFee?->transport_circle_id);
            $direction = $studentFee?->transport_direction;

            $isNewStudent = false;
            if ($student->joining_date == $student->start_date->value) {
                $isNewStudent = true;
            }

            $isMaleStudent = false;
            $isFemaleStudent = false;

            if ($student->gender == Gender::MALE->value) {
                $isMaleStudent = true;
            } elseif ($student->gender == Gender::FEMALE->value) {
                $isFemaleStudent = true;
            }

            (new AssignFeeInstallment)->execute(
                student: $student,
                feeInstallment: $feeInstallment,
                feeConcession: $feeConcession,
                transportCircle: $transportCircle,
                params: [
                    'direction' => $direction,
                    'is_new_student' => $isNewStudent,
                    'is_male_student' => $isMaleStudent,
                    'is_female_student' => $isFemaleStudent,
                ]
            );
        }

        \DB::commit();
    }

    public function update(FeeStructure $feeStructure, $uuid, Request $request): void
    {
        $feeInstallment = FeeInstallment::query()
            ->with('group')
            ->whereFeeStructureId($feeStructure->id)
            ->findByUuidOrFail($uuid);

        if ($feeInstallment->group->getMeta('is_custom')) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        if ($feeStructure->assigned_students) {
            $feeInstallment->title = $request->title;
            $feeInstallment->due_date = $request->due_date;
            $feeInstallment->late_fee = $request->late_fee;
            $feeInstallment->save();

            return;
        }

        $assignedFeeInstallment = Fee::query()
            ->whereFeeInstallmentId($feeInstallment->id)
            ->count();

        if ($assignedFeeInstallment) {
            throw ValidationException::withMessages(['message' => trans('finance.fee_structure.could_not_edit_assigned_installment', ['attribute' => $assignedFeeInstallment])]);
        }

        $params = $request->all();
        $params['action'] = 'update';

        (new CreateFeeInstallment)->execute(feeStructure: $feeStructure, feeInstallment: $feeInstallment, params: $params);
    }

    public function delete(FeeStructure $feeStructure, $uuid): void
    {
        $feeInstallment = FeeInstallment::query()
            ->whereFeeStructureId($feeStructure->id)
            ->findByUuidOrFail($uuid);

        if ($feeInstallment->group->getMeta('is_custom')) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        $paidFeeInstallments = Fee::whereFeeInstallmentId($feeInstallment->id)->where('paid', '>', 0)->count();

        if ($paidFeeInstallments) {
            throw ValidationException::withMessages(['message' => trans('finance.fee_structure.could_not_delete_installment', ['attribute' => $paidFeeInstallments])]);
        }

        $feeInstallment->delete();
    }
}
