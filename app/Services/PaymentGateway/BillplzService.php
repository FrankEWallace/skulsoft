<?php

namespace App\Services\PaymentGateway;

use App\Actions\Student\PayOnlineFee;
use App\Helpers\SysHelper;
use App\Models\Config\Config;
use App\Models\Finance\Transaction;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class BillplzService
{
    public function getResponse(Request $request)
    {
        $response = $this->validateSignature($request, 'callback');

        $status = Arr::get($response, 'status');
        $code = Arr::get($response, 'code');
        $transaction = Arr::get($response, 'transaction');

        if ($status == 'error') {
            if ($code == 'payment_failed') {
                $failedLogs = $transaction->failed_logs;
                $failedLogs[] = [
                    'name' => 'billplz',
                    'error' => $request->error,
                ];
                $transaction->failed_logs = $failedLogs;
                $transaction->save();
            }

            return false;
        }

        $this->processPayment($transaction);

        return true;
    }

    public function redirectUrl(Request $request)
    {
        $response = $this->validateSignature($request);

        $status = Arr::get($response, 'status');
        $code = Arr::get($response, 'code');
        $transaction = Arr::get($response, 'transaction');

        if ($status == 'error') {
            if ($code == 'payment_failed') {
                return view('messages.alert', [
                    'message' => trans('finance.payment_failed', ['attribute' => Arr::get($transaction, 'payment_gateway.reference_number')]),
                    'url' => route('app'),
                    'actionText' => trans('global.go_to', ['attribute' => trans('dashboard.dashboard')]),
                ]);
            } elseif ($code == 'transaction_not_found' || $code == 'invalid_signature') {
                abort(404);
            } elseif ($code == 'transaction_already_processed') {
                return view('messages.alert', [
                    'message' => trans('finance.payment_succeed', ['attribute' => Arr::get($transaction, 'payment_gateway.reference_number'), 'amount' => $transaction->amount->formatted]),
                    'url' => route('app'),
                    'actionText' => trans('global.go_to', ['attribute' => trans('dashboard.dashboard')]),
                ]);
            }
        }

        // if (app()->environment('local')) {
        $this->processPayment($transaction);
        // }

        return view('messages.alert', [
            'message' => trans('finance.payment_succeed', ['attribute' => Arr::get($transaction, 'payment_gateway.reference_number'), 'amount' => $transaction->amount->formatted]),
            'url' => route('app'),
            'actionText' => trans('global.go_to', ['attribute' => trans('dashboard.dashboard')]),
        ]);
    }

    private function processPayment(Transaction $transaction)
    {
        $paymentType = $transaction->head;

        if ($paymentType == 'student_fee') {
            $student = Student::find($transaction->transactionable_id);

            if ($transaction->user_id && empty(auth()->user())) {
                \Auth::loginUsingId($transaction->user_id);
                SysHelper::setTeam($transaction->period->team_id);
            }

            \DB::beginTransaction();

            (new PayOnlineFee)->studentFeePayment($student, $transaction);

            \DB::commit();
        }
    }

    private function validateSignature(Request $request, string $type = 'redirect')
    {
        if ($type == 'redirect') {
            $params = $request->billplz ?? [];
        } else {
            $params = $request->all();
        }

        $billId = Arr::get($params, 'id');

        $transaction = Transaction::query()
            ->with('period')
            ->where('payment_gateway->bill_id', $billId)
            ->first();

        if (! $transaction) {
            return [
                'status' => 'error',
                'code' => 'transaction_not_found',
            ];
        }

        if (! empty($transaction->processed_at->value)) {
            return [
                'status' => 'error',
                'code' => 'transaction_already_processed',
                'transaction' => $transaction,
            ];
        }

        $config = Config::query()
            ->where('team_id', $transaction->period->team_id)
            ->whereName('finance')
            ->first();

        $signature = Arr::get($config->value, 'billplz_signature');

        $signatureString = [];

        foreach ($params as $key => $value) {
            if ($key != 'x_signature') {
                if ($type == 'redirect') {
                    $signatureString[] = "billplz{$key}{$value}";
                } else {
                    $signatureString[] = "{$key}{$value}";
                }
            }
        }

        $signatureString = collect($signatureString)->sort()->toArray();
        $signatureString = implode('|', $signatureString);
        $generatedSignature = hash_hmac('sha256', $signatureString, $signature);

        if (! hash_equals($generatedSignature, Arr::get($params, 'x_signature'))) {
            return [
                'status' => 'error',
                'code' => 'invalid_signature',
                'transaction' => $transaction,
            ];
        }

        if (Arr::get($params, 'paid') === 'false' || Arr::get($params, 'paid') === false) {
            return [
                'status' => 'error',
                'code' => 'payment_failed',
                'transaction' => $transaction,
            ];
        }

        return [
            'transaction' => $transaction,
            'status' => 'success',
            'transaction' => $transaction,
        ];
    }
}
