<?php

namespace App\Services\Student;

use App\Actions\Student\CancelTransferStudent;
use App\Actions\Student\TransferStudent;
use App\Enums\OptionType;
use App\Http\Resources\OptionResource;
use App\Models\Option;
use App\Models\Student\Admission;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TransferService
{
    public function preRequisite(Request $request): array
    {
        $reasons = OptionResource::collection(Option::query()
            ->byTeam()
            ->where('type', OptionType::STUDENT_TRANSFER_REASON->value)
            ->get());

        return compact('reasons');
    }

    public function create(Request $request): void
    {
        \DB::beginTransaction();

        (new TransferStudent)->execute($request);

        \DB::commit();
    }

    public function update(Request $request, Student $student): void
    {
        if ($student->getMeta('transfer_request')) {
            throw ValidationException::withMessages(['message' => trans('student.transfer.could_not_perform_if_transfer_request')]);
        }

        if ($student->uuid != $request->student->uuid) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        \DB::beginTransaction();

        $student->end_date = $request->date;
        $student->setMeta([
            'transfer_certificate_number' => $request->transfer_certificate_number,
        ]);
        $student->save();

        $admission = Admission::query()
            ->whereId($student->admission_id)
            ->first();

        $admission->leaving_date = $request->date;
        $admission->transfer_reason_id = $request->reason_id;
        $admission->leaving_remarks = $request->remarks;
        $admission->save();

        \DB::commit();
    }

    public function deletable(Student $student): void
    {
        //
    }

    public function delete(Student $student): void
    {
        if ($student->getMeta('transfer_request')) {
            throw ValidationException::withMessages(['message' => trans('student.transfer.could_not_perform_if_transfer_request')]);
        }

        \DB::beginTransaction();

        (new CancelTransferStudent)->execute($student);

        \DB::commit();
    }
}
