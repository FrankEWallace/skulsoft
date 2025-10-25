<?php

namespace App\Services\Finance\PaymentGateway;

use App\Concerns\HasPaymentGateway;
use App\Contracts\Finance\PaymentGateway;
use App\Helpers\SysHelper;
use App\Models\Finance\Transaction;
use App\Models\Student\Student;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Stripe\PaymentIntent;
use Stripe\Stripe as StripeGateway;
use Stripe\StripeClient;

class Stripe implements PaymentGateway
{
    use HasPaymentGateway;

    public function getName(): string
    {
        return 'stripe';
    }

    public function getVersion(): string
    {
        return config('config.finance.stripe_version', 'NA');
    }

    public function isEnabled(): void
    {
        if (! config('config.finance.enable_stripe', false)) {
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

        StripeGateway::setApiKey(config('config.finance.stripe_secret'));

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => SysHelper::formatAmount($transaction->amount->value * $this->getMultiplier($request)),
                'currency' => $transaction->currency,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['message' => $e->getMessage()]);
        }

        $paymentGateway = $transaction->payment_gateway;
        $paymentGateway['payment_intent_id'] = $paymentIntent->id;
        $transaction->payment_gateway = $paymentGateway;
        $transaction->save();

        return [
            'token' => $transaction->uuid,
            'client_secret' => $paymentIntent->client_secret,
            'amount' => SysHelper::formatAmount($transaction->amount->value * $this->getMultiplier($request), $transaction->currency),
            'key' => config('config.finance.stripe_client'),
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

        $stripe = new StripeClient(config('config.finance.stripe_secret'));

        $paymentDetail = $stripe->paymentIntents->retrieve(Arr::get($transaction->payment_gateway, 'payment_intent_id'), []);

        if ($paymentDetail->status != 'succeeded') {
            throw ValidationException::withMessages(['message' => trans('finance.payment_failed')]);
        }

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
