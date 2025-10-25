<?php

namespace App\Services\Finance;

use App\Models\Finance\Transaction;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TransactionActionService
{
    public function updateClearingDate(Request $request, Transaction $transaction)
    {
        if (! $transaction->can_edit) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }

        $request->validate([
            'clearing_date' => ['required', 'date_format:Y-m-d'],
        ]);

        if ($request->clearing_date < $transaction->date->value) {
            throw ValidationException::withMessages(['clearing_date' => trans('finance.transaction.clearing_date_before_transaction_date')]);
        }

        $transaction->load('payments.method');

        $payments = $transaction->payments->filter(function ($payment) {
            return $payment->method->getConfig('has_clearing_date');
        });

        foreach ($payments as $payment) {
            $details = $payment->details;

            $details['clearing_date'] = $request->clearing_date;

            $payment->details = $details;
            $payment->save();
        }

        return $transaction;
    }
}
