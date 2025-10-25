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

class Paypal implements PaymentGateway
{
    use HasPaymentGateway;

    public function getName(): string
    {
        return 'paypal';
    }

    public function getVersion(): string
    {
        return config('config.finance.paypal_version', 'NA');
    }

    public function isEnabled(): void
    {
        if (! config('config.finance.enable_paypal', false)) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_operation')]);
        }
    }

    public function getMultiplier(Request $request): float
    {
        return 1;
    }

    public function supportedCurrencies(): array
    {
        return [];
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
            'key' => config('config.finance.paypal_client'),
            'currency' => $transaction->currency,
            'name' => $student->name,
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

        $clientId = config('config.finance.paypal_client');
        $secret = config('config.finance.paypal_secret');

        $uri = config('config.finance.enable_live_paypal_mode') ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

        $response = Http::asForm()->withBasicAuth($clientId, $secret)->post($uri.'/v1/oauth2/token', [
            'grant_type' => 'client_credentials',
        ]);

        $accessToken = Arr::get($response->json(), 'access_token');

        $response = Http::withToken($accessToken)->get($uri.'/v2/checkout/orders/'.$request->input('payment_detail.id'));

        $status = Arr::get($response->json(), 'status');

        if ($status != 'COMPLETED') {
            throw ValidationException::withMessages(['message' => trans('finance.payment_failed')]);
        }

        // Confirm amount

        $paymentGateway = $transaction->payment_gateway;
        $paymentGateway['payment_id'] = $request->input('payment_detail.id');
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
        ];
        $transaction->failed_logs = $failedLogs;
        $transaction->save();

        return $transaction;
    }
}
