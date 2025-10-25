<?php

namespace App\Actions\Student;

use App\Models\Student\Admission;
use Illuminate\Http\Request;

class TransferStudent
{
    public function execute(Request $request): void
    {
        $student = $request->student;

        $student->end_date = $request->date;
        $student->setMeta([
            'transfer_certificate_number' => $request->transfer_certificate_number,
            'transfer_request' => $request->boolean('transfer_request'),
        ]);
        $student->save();

        $admission = Admission::query()
            ->whereId($student->admission_id)
            ->first();

        $admission->leaving_date = $request->date;
        $admission->transfer_reason_id = $request->reason_id;
        $admission->leaving_remarks = $request->remarks;
        $admission->save();

        foreach ($student->fees as $studentFee) {
            $studentFee->setMeta([
                'total_before_transfer' => $studentFee->total->value,
            ]);
            $studentFee->total = $studentFee->paid->value;
            $studentFee->save();

            foreach ($studentFee->records as $record) {
                $record->setMeta([
                    'amount_before_transfer' => $record->amount->value,
                ]);
                $record->amount = $record->paid->value;
                $record->save();
            }
        }
    }
}
