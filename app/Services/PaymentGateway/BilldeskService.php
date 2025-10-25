<?php

namespace App\Services\PaymentGateway;

use App\Actions\Student\PayOnlineFee;
use App\Helpers\SysHelper;
use App\Models\Config\Config;
use App\Models\Finance\Transaction;
use App\Models\Student\Student;
use App\Support\PaymentGatewayMultiAccountSeparator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Io\Billdesk\Client\Hmacsha256\JWEHS256Helper;

class BilldeskService
{
    use PaymentGatewayMultiAccountSeparator;

    public function checkStatus(Request $request)
    {
        if (empty($request->query('reference_number'))) {
            return 'Enter reference number';
        }

        $transaction = Transaction::query()
            ->with('period')
            ->where('payment_gateway->reference_number', $request->query('reference_number'))
            ->first();

        if (! $transaction) {
            abort(404);
        }

        $pgAccount = Arr::get($transaction->payment_gateway, 'pg_account');

        $secret = $this->getCredential(config('config.finance.billdesk_secret'), $pgAccount);
        $client = $this->getCredential(config('config.finance.billdesk_client'), $pgAccount);
        $merchantId = $this->getCredential(config('config.finance.billdesk_merchant'), $pgAccount);
        $mode = (bool) config('config.finance.enable_live_billdesk_mode');
        $version = config('config.finance.billdesk_version');

        $request = [
            'mercid' => $merchantId,
            'orderid' => $request->query('reference_number'),
            'refund_details' => true,
        ];

        $client = new JWEHS256Helper($secret);

        $traceId = strtoupper(Str::random(10));
        $time = time();

        try {
            $response = $client->encryptAndSign(json_encode($request), [
                'BD-Traceid' => $traceId,
                'BD-Timestamp' => $time,
                'Content-Type' => 'application/jose',
                'Accept' => 'application/jose',
                'clientid' => strtolower($merchantId),
            ]);
        } catch (\Exception $e) {
            dd($e->getMessage());
        }

        $billdeskUrl = 'https://pguat.billdesk.io';

        if ($version == '1.5') {
            $billdeskUrl = 'https://uat1.billdesk.com/u2';
        }

        if ($mode) {
            $billdeskUrl = 'https://api.billdesk.com';
        }

        $response = Http::withHeaders([
            'BD-Traceid' => $traceId,
            'BD-Timestamp' => $time,
            'Content-Type' => 'application/jose',
            'Accept' => 'application/jose',
        ])
            ->withBody($response, 'application/jose')
            ->post($billdeskUrl.'/payments/ve1_2/transactions/get');

        try {
            $response = $client->verifyAndDecrypt($response->body());
        } catch (\Exception $e) {
            dd('Invalid checksum');
        }

        $response = json_decode($response, true);

        dd($response);
    }

    public function getResponse(Request $request)
    {
        $params = $request->all();

        $referenceNumber = Arr::get($params, 'orderid');

        $transaction = Transaction::query()
            ->with('period')
            ->where('payment_gateway->reference_number', $referenceNumber)
            ->where('created_at', '>=', now()->subMinutes(30))
            ->first();

        if (! $transaction) {
            abort(404);
        }

        $config = Config::query()
            ->where('team_id', $transaction->period->team_id)
            ->whereName('finance')
            ->first();

        $pgVersion = Arr::get($transaction->payment_gateway, 'version');

        if (! in_array($pgVersion, ['1.2', '1.5', 1.2, 1.5])) {
            return view('messages.alert', [
                'message' => trans('finance.pg_version_mismatch', ['attribute' => $referenceNumber]),
                'url' => route('app'),
                'actionText' => trans('global.go_to', ['attribute' => trans('dashboard.dashboard')]),
            ]);
        }

        $pgAccount = Arr::get($transaction->payment_gateway, 'pg_account');

        $secret = $this->getCredential(Arr::get($config->value, 'billdesk_secret'), $pgAccount);

        $client = new JWEHS256Helper($secret);

        $transactionResponse = Arr::get($request->all(), 'transaction_response');

        if (empty($transactionResponse)) {
            return view('messages.alert', [
                'message' => trans('finance.no_response_received', ['attribute' => $referenceNumber]),
                'url' => route('app'),
                'actionText' => trans('global.go_to', ['attribute' => trans('dashboard.dashboard')]),
            ]);
        }

        try {
            $response = $client->verifyAndDecrypt($transactionResponse);
        } catch (\Exception $e) {
            abort(404);
        }

        $response = json_decode($response, true);

        $referenceNumber = Arr::get($response, 'orderid');

        $transaction->payment_gateway = array_merge($transaction->payment_gateway, [
            'transactionid' => Arr::get($response, 'transactionid'),
            'bankid' => Arr::get($response, 'bankid'),
            'payment_method_type' => Arr::get($response, 'payment_method_type'),
        ]);
        $transaction->save();

        $paymentType = Arr::get($response, 'additional_info.additional_info1');
        $transactionUuid = Arr::get($response, 'additional_info.additional_info2');

        $url = route('app');
        $actionText = trans('global.go_to', ['attribute' => trans('dashboard.dashboard')]);

        if ($paymentType == 'student_fee') {
            $student = Student::find($transaction->transactionable_id);
            $url = url("app/students/{$student->uuid}/fee");
            $actionText = trans('global.go_to', ['attribute' => trans('student.fee.fee')]);

            if (empty($transaction->user_id)) {
                $url = url('app/payment');
            }
        }

        if ($transactionUuid != $transaction->uuid) {
            return view('messages.alert', [
                'message' => trans('finance.id_mismatch', ['attribute' => $referenceNumber]),
                'url' => $url,
                'actionText' => $actionText,
            ]);
        }

        $status = Arr::get($response, 'auth_status');

        if ($status !== '0300') {
            return view('messages.alert', [
                'message' => trans('finance.transaction_failed', ['attribute' => $referenceNumber]),
                'url' => $url,
                'actionText' => $actionText,
            ]);
        }

        $amount = Arr::get($response, 'amount');

        if ($amount != $transaction->amount->value) {
            return view('messages.alert', [
                'message' => trans('finance.amount_mismatch', ['attribute' => $referenceNumber]),
                'url' => $url,
                'actionText' => $actionText,
            ]);
        }

        $refundInfo = Arr::get($response, 'refundInfo', []);

        if (count($refundInfo)) {
            return view('messages.alert', [
                'message' => trans('finance.amount_refunded', ['attribute' => $referenceNumber]),
                'url' => $url,
                'actionText' => $actionText,
            ]);
        }

        if ($paymentType == 'student_fee') {
            if ($transaction->user_id && empty(auth()->user())) {
                \Auth::loginUsingId($transaction->user_id);
                SysHelper::setTeam($transaction->period->team_id);
            }

            \DB::beginTransaction();

            (new PayOnlineFee)->studentFeePayment($student, $transaction);

            \DB::commit();

            return view('messages.alert', [
                'message' => trans('finance.payment_succeed', ['amount' => $transaction->amount->formatted, 'attribute' => $referenceNumber]),
                'type' => 'success',
                'url' => $url,
                'actionText' => $actionText,
            ]);
        }

        return view('messages.alert', [
            'message' => trans('general.errors.invalid_operation'),
            'url' => $url,
            'actionText' => $actionText,
        ]);
    }

    public function cancel(Request $request)
    {
        return view('messages.alert', [
            'message' => trans('finance.payment_cancelled', ['attribute' => $request->orderid]),
            'url' => route('app'),
            'actionText' => trans('global.go_to', ['attribute' => trans('dashboard.dashboard')]),
        ]);
    }
}
