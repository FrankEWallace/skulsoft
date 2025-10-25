<?php

namespace App\Actions\Student;

use App\Models\Finance\Transaction;
use App\Models\Student\Fee;
use App\Models\Student\Student;

class DeleteFeePayment
{
    public function execute(Student $student): void
    {
        $transactions = Transaction::query()
            ->with('payments.ledger', 'records.ledger')
            ->whereHead('student_fee')
            ->where('transactionable_type', 'Student')
            ->where('transactionable_id', $student->id)
            ->get();

        foreach ($transactions as $transaction) {
            foreach ($transaction->payments->whereNotNull('ledger_id') as $payment) {
                $ledger = $payment->ledger;
                $ledger->reversePrimaryBalance($transaction->type, $payment->amount->value);
            }

            foreach ($transaction->records->whereNotNull('ledger_id') as $record) {
                $ledger = $record->ledger;
                $ledger->reverseSecondaryBalance($transaction->type, $record->amount->value);
            }

            $transaction->delete();
        }

        Fee::query()
            ->where('student_id', $student->id)
            ->delete();
    }
}
