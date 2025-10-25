<?php

namespace App\Services\Student;

use App\Actions\Finance\CancelTransaction;
use App\Models\Finance\FeeRefund;
use App\Models\Finance\Transaction;
use App\Models\Student\Student;
use Illuminate\Http\Request;

class FeeRefundActionService
{
    public function cancel(Request $request, Student $student, string $uuid): void
    {
        $feeRefund = FeeRefund::query()
            ->withTransaction()
            ->where('student_id', $student->id)
            ->whereUuid($uuid)
            ->whereIsCancelled(0)
            ->getOrFail(trans('student.fee_refund.fee_refund'));

        $transaction = Transaction::query()
            ->find($feeRefund->transaction_id);

        \DB::beginTransaction();

        (new CancelTransaction)->execute($request, $transaction);

        $feeRefund->is_cancelled = true;
        $feeRefund->save();

        \DB::commit();
    }
}
