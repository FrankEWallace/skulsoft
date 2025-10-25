<?php

namespace App\Concerns;

use App\Models\Finance\Transaction;
use Illuminate\Validation\ValidationException;

trait HasPaymentGateway
{
    public function validateCurrency(Transaction $transaction, array $supportedCurrencies, array $unsupportedCurrencies): void
    {
        if ($supportedCurrencies && ! in_array($transaction->currency, $supportedCurrencies)) {
            throw ValidationException::withMessages(['message' => trans('finance.unsupported_currency', ['currency' => $transaction->currency])]);
        }

        if ($unsupportedCurrencies && in_array($transaction->currency, $unsupportedCurrencies)) {
            throw ValidationException::withMessages(['message' => trans('finance.unsupported_currency', ['currency' => $transaction->currency])]);
        }
    }
}
