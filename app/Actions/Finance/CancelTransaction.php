<?php

namespace App\Actions\Finance;

use App\Models\Finance\Transaction;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CancelTransaction
{
    public function execute(Request $request, Transaction $transaction): void
    {
        if ($transaction->is_online) {
            throw ValidationException::withMessages(['message' => trans('finance.payment.could_not_cancel_online_payment')]);
        }

        if ($transaction->cancelled_at->value) {
            throw ValidationException::withMessages(['message' => trans('finance.payment.already_cancelled')]);
        }

        if ($transaction->rejected_at->value) {
            throw ValidationException::withMessages(['message' => trans('finance.payment.already_rejected')]);
        }

        $transaction->loadMissing('payments.ledger', 'records.ledger');

        foreach ($transaction->payments->whereNotNull('ledger_id') as $payment) {
            $ledger = $payment->ledger;
            $ledger->reversePrimaryBalance($transaction->type, $payment->amount->value);
        }

        foreach ($transaction->records->whereNotNull('ledger_id') as $record) {
            $ledger = $record->ledger;
            $ledger->reverseSecondaryBalance($transaction->type, $record->amount->value);
        }

        if ($request->boolean('is_rejected')) {
            $transaction->rejected_at = now()->toDateTimeString();
            $transaction->rejection_remarks = $request->rejection_remarks;
            $transaction->rejection_record = [
                'rejected_date' => $request->rejected_date,
                'rejection_charge' => $request->rejection_charge,
                'rejected_by' => auth()->user()?->name,
            ];
            $transaction->save();

            return;
        }

        $transaction->cancelled_at = now()->toDateTimeString();
        $transaction->cancellation_remarks = $request->cancellation_remarks;
        $transaction->setMeta([
            'cancelled_by_id' => auth()->id(),
            'cancelled_by_name' => auth()->user()?->name,
        ]);
        $transaction->save();
    }
}
