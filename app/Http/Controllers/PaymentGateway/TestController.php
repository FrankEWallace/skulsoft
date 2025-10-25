<?php

namespace App\Http\Controllers\PaymentGateway;

use App\Contracts\Finance\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Models\Finance\Transaction;
use App\Models\Student\Student;
use App\Services\Student\OnlinePaymentService;
use App\Support\PaymentGatewayMultiAccountSeparator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Io\Billdesk\Client\Hmacsha256\JWEHS256Helper;

class TestController extends Controller
{
    use PaymentGatewayMultiAccountSeparator;

    public function initiate(Request $request, string $gateway, OnlinePaymentService $service, PaymentGateway $paymentGateway)
    {
        if ($gateway != 'billdesk') {
            abort(404);
        }

        $service->setFinanceConfig(1);

        $request->merge([
            'date' => today()->toDateString(),
            'amount' => 10,
            'gateway' => $gateway,
        ]);

        $student = Student::query()
            ->summaryForGuest()
            ->first();

        $referenceNumber = Str::random(10);

        $transaction = new Transaction;
        $transaction->uuid = (string) Str::uuid();
        $transaction->amount = $request->amount;
        $transaction->payment_gateway = [
            'pg_account' => $request->query('account', $this->getFirstAccount(config('config.finance.billdesk_secret'))),
            'reference_number' => $referenceNumber,
            'return_url' => url('payment/test/billdesk/status'),
        ];

        $data = $paymentGateway->initiatePayment($request, $student, $transaction);

        return view('gateways.test.billdesk', [
            'merchantId' => Arr::get($data, 'merchant_id'),
            'bdOrderId' => Arr::get($data, 'bd_order_id'),
            'authToken' => Arr::get($data, 'auth_token'),
            'referenceNumber' => $referenceNumber,
            'childWindow' => Arr::get($data, 'child_window'),
            'returnUrl' => url('payment/test/billdesk/status'),
        ]);
    }

    public function status(Request $request, string $gateway, OnlinePaymentService $service)
    {
        if ($gateway != 'billdesk') {
            abort(404);
        }

        $service->setFinanceConfig(1);

        if (empty($request->all()) && config('config.finance.billdesk_version') == '1.2') {
            return view('gateways.response.billdesk');
        }

        $pgAccount = $this->getFirstAccount(config('config.finance.billdesk_secret'));

        $secret = $this->getCredential(config('config.finance.billdesk_secret'), $pgAccount);

        $client = new JWEHS256Helper($secret);

        try {
            $response = $client->verifyAndDecrypt(Arr::get($request->all(), 'transaction_response'));
        } catch (\Exception $e) {
            dd($e->getMessage());
        }

        $response = json_decode($response, true);

        dd($response);
    }
}
