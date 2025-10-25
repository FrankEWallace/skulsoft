<?php

namespace App\Services\Finance\PaymentGateway;

use App\Concerns\HasPaymentGateway;
use App\Contracts\Finance\PaymentGateway;
use App\Helpers\SysHelper;
use App\Models\Finance\Transaction;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class Payzone implements PaymentGateway
{
    use HasPaymentGateway;

    public function getName(): string
    {
        return 'payzone';
    }

    public function getVersion(): string
    {
        return config('config.finance.payzone_version', 'NA');
    }

    public function isEnabled(): void
    {
        if (! config('config.finance.enable_payzone', false)) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_operation')]);
        }
    }

    public function getMultiplier(Request $request): float
    {
        return 1;
    }

    public function supportedCurrencies(): array
    {
        return ['MAD'];
    }

    public function unsupportedCurrencies(): array
    {
        return [];
    }

    public function initiatePayment(Request $request, Student $student, Transaction $transaction): array
    {
        $this->validateCurrency($transaction, $this->supportedCurrencies(), $this->unsupportedCurrencies());

        $merchant = config('config.finance.payzone_merchant');
        $secretKey = config('config.finance.payzone_secret_key');
        $notificationKey = config('config.finance.payzone_notification_key');
        $url = config('config.finance.enable_live_payzone_mode') ? 'https://payzone.ma/pwthree/launch' : 'https://payment-sandbox.payzone.ma/pwthree/launch';

        $chargeId = Arr::get($transaction->payment_gateway, 'reference_number');

        $payload = [
            'merchantAccount' => $merchant,
            'timestamp' => time(),
            'skin' => 'vps-1-vue',

            'customerId' => 'payplus-paywall-poc',
            'customerCountry' => 'MA',
            'customerLocale' => 'en_US',

            'chargeId' => $chargeId,
            'price' => $transaction->amount->value * $this->getMultiplier($request),
            'currency' => $transaction->currency,
            // Temporarily testing the price and currency
            // 'price' => 10,
            // 'currency' => 'MAD',
            'description' => $student->name,

            'chargeProperties' => [
                'param1' => 'student_fee',
                'param2' => $transaction->uuid,
            ],
            'mode' => 'DEEP_LINK',
            'paymentMethod' => 'CREDIT_CARD',
            'showPaymentProfiles' => 'true',
            'flowCompletionUrl' => (string) url('/app/students/'.$student->uuid.'/fee'),
            'successUrl' => (string) url('/app/students/'.$student->uuid.'/fee'),
            'callbackUrl' => (string) url('/payment/payzone/response'),
        ];

        $paymentGateway = $transaction->payment_gateway;
        $paymentGateway['charge_id'] = $chargeId;
        $transaction->payment_gateway = $paymentGateway;
        $transaction->save();

        $jsonPayload = json_encode($payload);
        $signature = hash('sha256', $secretKey.$jsonPayload);

        return [
            'token' => $transaction->uuid,
            'payload' => $jsonPayload,
            'signature' => $signature,
            'pg_url' => $url,
            'amount' => SysHelper::formatAmount($transaction->amount->value * $this->getMultiplier($request), $transaction->currency),
            'key' => config('config.finance.payzone_merchant'),
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
        ];
        $transaction->failed_logs = $failedLogs;
        $transaction->save();

        return $transaction;
    }
}
