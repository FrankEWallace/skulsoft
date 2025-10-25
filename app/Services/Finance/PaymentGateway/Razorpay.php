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
use Razorpay\Api\Api as RazorpayApi;

class Razorpay implements PaymentGateway
{
    use HasPaymentGateway;

    public function getName(): string
    {
        return 'razorpay';
    }

    public function getVersion(): string
    {
        return config('config.finance.razorpay_version', 'NA');
    }

    public function isEnabled(): void
    {
        if (! config('config.finance.enable_razorpay', false)) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_operation')]);
        }
    }

    public function getMultiplier(Request $request): float
    {
        return 100;
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
            'key' => config('config.finance.razorpay_client'),
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

        $api = new RazorpayApi(config('config.finance.razorpay_client'), config('config.finance.razorpay_secret'));
        $paymentDetail = $api->payment->fetch($request->payment_id);
        $paymentDetail = $paymentDetail->toArray();

        $status = Arr::get($paymentDetail, 'status');

        if ($status != 'authorized' || $request->token != Arr::get($paymentDetail, 'notes.token')) {
            throw ValidationException::withMessages(['message' => trans('finance.payment_failed')]);
        }

        // Confirm amount

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
