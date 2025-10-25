<?php

namespace App\Services\Finance\PaymentGateway;

use App\Concerns\HasPaymentGateway;
use App\Contracts\Finance\PaymentGateway;
use App\Models\Finance\Transaction;
use App\Models\Student\Student;
use App\Support\CcavenueCrypto;
use App\Support\PaymentGatewayMultiAccountSeparator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class Ccavenue implements PaymentGateway
{
    use HasPaymentGateway, PaymentGatewayMultiAccountSeparator;

    public function getName(): string
    {
        return 'ccavenue';
    }

    public function getVersion(): string
    {
        return config('config.finance.ccavenue_version', 'NA');
    }

    public function isEnabled(): void
    {
        if (! config('config.finance.enable_ccavenue', false)) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_operation')]);
        }
    }

    public function getMultiplier(Request $request): float
    {
        return 1;
    }

    public function supportedCurrencies(): array
    {
        return ['INR'];
    }

    public function unsupportedCurrencies(): array
    {
        return [];
    }

    public function initiatePayment(Request $request, Student $student, Transaction $transaction): array
    {
        $this->validateCurrency($transaction, $this->supportedCurrencies(), $this->unsupportedCurrencies());

        $pgAccount = Arr::get($transaction->payment_gateway, 'pg_account');

        $secret = $this->getCredential(config('config.finance.ccavenue_secret'), $pgAccount);
        $client = $this->getCredential(config('config.finance.ccavenue_client'), $pgAccount);
        $merchantId = $this->getCredential(config('config.finance.ccavenue_merchant'), $pgAccount);

        $data = [
            'tid' => (int) (microtime(true) * 1000),
            'merchant_id' => $merchantId,
            'order_id' => Arr::get($transaction->payment_gateway, 'reference_number'),
            'amount' => $transaction->amount->value,
            'currency' => $transaction->currency,
            'redirect_url' => url('payment/ccavenue/response'),
            'cancel_url' => url('payment/ccavenue/cancel'),
            'language' => 'EN',
            'merchant_param1' => 'student_fee',
            'merchant_param2' => $transaction->uuid,
            'merchant_param3' => $student->code_number,
            'merchant_param4' => $student->name,
            'merchant_param5' => $student->course_name.' '.$student->batch_name,
        ];

        $merchantData = '';
        foreach ($data as $key => $value) {
            $merchantData .= $key.'='.$value.'&';
        }

        $pgUrl = 'https://test.ccavenue.com';

        if (config('config.finance.enable_live_ccavenue_mode')) {
            $pgUrl = 'https://secure.ccavenue.com';
        }

        $encryptedString = (new CcavenueCrypto)->encrypt($merchantData, $secret);

        return [
            'token' => $transaction->uuid,
            'enc_string' => $encryptedString,
            'access_code' => $client,
            'pg_url' => $pgUrl.'/transaction/transaction.do?command=initiateTransaction',
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
