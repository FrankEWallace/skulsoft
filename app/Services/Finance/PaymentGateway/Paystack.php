<?php

namespace App\Services\Finance\PaymentGateway;

use App\Concerns\HasPaymentGateway;
use App\Contracts\Finance\PaymentGateway;
use App\Helpers\SysHelper;
use App\Models\Finance\Transaction;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class Paystack implements PaymentGateway
{
    use HasPaymentGateway;

    public function getName(): string
    {
        return 'paystack';
    }

    public function getVersion(): string
    {
        return config('config.finance.paystack_version', 'NA');
    }

    public function isEnabled(): void
    {
        if (! config('config.finance.enable_paystack', false)) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_operation')]);
        }
    }

    public function getMultiplier(Request $request): float
    {
        return 100;
    }

    public function supportedCurrencies(): array
    {
        return ['NGN', 'GHS'];
    }

    public function unsupportedCurrencies(): array
    {
        return [];
    }

    public function initiatePayment(Request $request, Student $student, Transaction $transaction): array
    {
        $this->validateCurrency($transaction, $this->supportedCurrencies(), $this->unsupportedCurrencies());

        return [
            'token' => $transaction->uuid,
            'amount' => SysHelper::formatAmount($transaction->amount->value * $this->getMultiplier($request), $transaction->currency),
            'key' => config('config.finance.paystack_client'),
            'currency' => $transaction->currency,
            'name' => $student->name,
            'email' => $student->email,
            'description' => $student->course_name.' '.$student->batch_name,
            'icon' => config('config.assets.icon'),
        ];
    }

    private function getTransaction(Request $request)
    {
        $transaction = Transaction::query()
            ->whereUuid($request->token)
            ->first();

        if (! $transaction) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        return $transaction;
    }

    public function confirmPayment(Request $request): Transaction
    {
        $transaction = $this->getTransaction($request);

        $response = Http::withToken(config('config.finance.paystack_secret'))
            ->get('https://api.paystack.co/transaction/verify/'.$request->payment_id);

        $body = json_decode($response->body(), true);

        if (Arr::get($body, 'status') !== true) {
            throw ValidationException::withMessages(['message' => trans('finance.payment_failed')]);
        }

        if (Arr::get($body, 'data.amount') != $transaction->amount->value * $this->getMultiplier($request)) {
            throw ValidationException::withMessages(['message' => trans('finance.amount_mismatch')]);
        }

        $metaData = Arr::get($body, 'data.metadata');
        $customField = Arr::first(Arr::get($metaData, 'custom_fields'));

        if (Arr::get($customField, 'value') !== $transaction->uuid) {
            throw ValidationException::withMessages(['message' => trans('finance.invalid_token')]);
        }

        $paymentGateway = $transaction->payment_gateway;
        $paymentGateway['payment_id'] = $request->payment_id;
        $transaction->payment_gateway = $paymentGateway;

        return $transaction;
    }

    public function failPayment(Request $request): Transaction
    {
        $transaction = $this->getTransaction($request);

        $failedLogs = $transaction->failed_logs;
        $failedLogs[] = [
            'name' => $this->getName(),
            'error' => $request->error,
            'failed_at' => now()->toDateTimeString(),
        ];
        $transaction->failed_logs = $failedLogs;
        $transaction->save();

        return $transaction;
    }
}
