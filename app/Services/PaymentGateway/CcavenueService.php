<?php

namespace App\Services\PaymentGateway;

use App\Actions\Student\PayOnlineFee;
use App\Helpers\SysHelper;
use App\Models\Config\Config;
use App\Models\Finance\Transaction;
use App\Models\Student\Student;
use App\Support\CcavenueCrypto;
use App\Support\PaymentGatewayMultiAccountSeparator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class CcavenueService
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

        $secret = $this->getCredential(config('config.finance.ccavenue_secret'), $pgAccount);
        $client = $this->getCredential(config('config.finance.ccavenue_client'), $pgAccount);
        $mode = (bool) config('config.finance.enable_live_ccavenue_mode');

        $merchantJsonData = [
            'order_no' => $request->query('reference_number'),
            'reference_no' => '',
        ];

        $merchantData = json_encode($merchantJsonData);
        $encryptedData = (new CcavenueCrypto)->encrypt($merchantData, $secret);
        $finalData = 'enc_request='.$encryptedData.'&access_code='.$client.'&command=orderStatusTracker&request_type=JSON&response_type=JSON&version=1.2';

        if ($mode) {
            $url = 'https://api.ccavenue.com/apis/servlet/DoWebTrans';
        } else {
            $url = 'https://apitest.ccavenue.com/apis/servlet/DoWebTrans';
        }

        $response = Http::acceptJson()
            ->post($url.'?'.$finalData, []);

        $information = explode('&', $response->body());
        $response = $this->getTransactionData(collect($information), 'enc_response');

        $response = (new CcavenueCrypto)->decrypt(trim($response), $secret);
        $response = json_decode($response);

        dd($response);
    }

    public function getResponse(Request $request)
    {
        $referenceNumber = $request->orderNo;

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

        $pgAccount = Arr::get($transaction->payment_gateway, 'pg_account');

        $secret = $this->getCredential(Arr::get($config->value, 'ccavenue_secret'), $pgAccount);

        $string = (new CcavenueCrypto)->decrypt($request->encResp, $secret);

        $data = collect(explode('&', $string));

        $transaction->payment_gateway = array_merge($transaction->payment_gateway, [
            'tracking_id' => $this->getTransactionData($data, 'tracking_id'),
            'bank_ref_no' => $this->getTransactionData($data, 'bank_ref_no'),
        ]);
        $transaction->save();

        $paymentType = $this->getTransactionData($data, 'merchant_param1');
        $transactionUuid = $this->getTransactionData($data, 'merchant_param2');

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

        $orderStatus = $this->getTransactionData($data, 'order_status');

        if ($orderStatus !== 'Success') {
            return view('messages.alert', [
                'message' => trans('finance.transaction_failed', ['attribute' => $referenceNumber]),
                'url' => $url,
                'actionText' => $actionText,
            ]);
        }

        $amount = $this->getTransactionData($data, 'amount');

        if ($amount != $transaction->amount->value) {
            return view('messages.alert', [
                'message' => trans('finance.amount_mismatch', ['attribute' => $referenceNumber]),
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

    private function getTransactionData(Collection $data, $key)
    {
        $item = $data->first(function ($item) use ($key) {
            return starts_with($item, $key.'=');
        });

        return explode('=', $item)[1] ?? null;
    }

    public function cancel(Request $request)
    {
        $referenceNumber = $request->orderNo;

        return view('messages.alert', [
            'message' => trans('finance.payment_cancelled', ['attribute' => $referenceNumber]),
            'url' => route('app'),
            'actionText' => trans('global.go_to', ['attribute' => trans('dashboard.dashboard')]),
        ]);
    }
}
