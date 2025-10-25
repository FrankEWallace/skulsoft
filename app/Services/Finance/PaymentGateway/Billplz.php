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

class Billplz implements PaymentGateway
{
    use HasPaymentGateway;

    public function getName(): string
    {
        return 'billplz';
    }

    public function getVersion(): string
    {
        return config('config.finance.billplz_version', 'NA');
    }

    public function isEnabled(): void
    {
        if (! config('config.finance.enable_billplz', false)) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_operation')]);
        }
    }

    public function getMultiplier(Request $request): float
    {
        return 100;
    }

    public function supportedCurrencies(): array
    {
        return ['MYR'];
    }

    public function unsupportedCurrencies(): array
    {
        return [];
    }

    public function initiatePayment(Request $request, Student $student, Transaction $transaction): array
    {
        $this->validateCurrency($transaction, $this->supportedCurrencies(), $this->unsupportedCurrencies());

        $url = 'https://www.billplz-sandbox.com/api/v3';

        if (config('config.finance.enable_live_billplz_mode', true)) {
            $url = 'https://www.billplz.com/api/v3';
        }

        $referenceNumber = Arr::get($transaction->payment_gateway, 'reference_number');

        $response = Http::withBasicAuth(config('config.finance.billplz_secret'), '')
            ->post("{$url}/bills", [
                'collection_id' => config('config.finance.billplz_client'),
                'email' => $student->email ?? 'test@test.com',
                'name' => $student->name,
                'amount' => $transaction->amount->value * 100,
                'callback_url' => url('/payment/billplz/response'),
                'redirect_url' => (string) url('/payment/billplz/redirect'),
                'description' => $student->course_name.' '.$student->batch_name,
                'reference_1_label' => 'Reference Number',
                'reference_1' => $referenceNumber,
            ]);

        $body = json_decode($response->body(), true);

        if ($response->status() !== 200) {
            throw ValidationException::withMessages(['message' => Arr::get($body, 'error.message')]);
        }

        $paymentGateway = $transaction->payment_gateway;
        $paymentGateway['bill_id'] = Arr::get($body, 'id');
        $transaction->payment_gateway = $paymentGateway;
        $transaction->save();

        return [
            'token' => $transaction->uuid,
            'amount' => SysHelper::formatAmount($transaction->amount->value * $this->getMultiplier($request), $transaction->currency),
            'currency' => $transaction->currency,
            'name' => $student->name,
            'email' => $student->email,
            'description' => $student->course_name.' '.$student->batch_name,
            'icon' => config('config.assets.icon'),
            'pg_url' => Arr::get($body, 'url'),
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

        throw ValidationException::withMessages(['message' => 'test']);

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
