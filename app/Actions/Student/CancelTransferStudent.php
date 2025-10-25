<?php

namespace App\Actions\Student;

use App\Models\Student\Admission;
use App\Models\Student\Student;

class CancelTransferStudent
{
    public function execute(Student $student): void
    {
        $student->end_date = null;
        $student->save();

        $admission = Admission::query()
            ->whereId($student->admission_id)
            ->first();

        $admission->leaving_date = null;
        $admission->transfer_reason_id = null;
        $admission->leaving_remarks = null;
        $admission->save();

        foreach ($student->fees as $studentFee) {
            $studentFee->total = $studentFee->getMeta('total_before_transfer');
            $studentFee->resetMeta(['total_before_transfer']);
            $studentFee->save();

            foreach ($studentFee->records as $record) {
                $record->amount = $record->getMeta('amount_before_transfer');
                $record->resetMeta(['amount_before_transfer']);
                $record->save();
            }
        }
    }
}
