<?php

namespace App\Actions\PaymentGateway;

use App\Actions\Student\PayOnlineFee;
use App\Models\Finance\Transaction;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class UpdateBillplzPayment
{
    public function execute(Request $request, Student $student, Transaction $transaction)
    {
        $billId = Arr::get($transaction->payment_gateway, 'bill_id');
        $paymentGateway = $transaction->payment_gateway;

        $url = 'https://www.billplz-sandbox.com/api/v3';

        if (config('config.finance.enable_live_billplz_mode', true)) {
            $url = 'https://www.billplz.com/api/v3';
        }

        $response = Http::withBasicAuth(config('config.finance.billplz_secret'), '')
            ->get("{$url}/bills/{$billId}");

        if ($response->failed()) {
            throw ValidationException::withMessages(['message' => trans('student.fee.could_not_update_payment_status')]);
        }

        $response = $response->json();

        if (Arr::get($response, 'paid') === 'false' || Arr::get($response, 'paid') === false) {
            $paymentGateway['status'] = 'updated';

            $transaction->payment_gateway = $paymentGateway;
            $transaction->save();

            throw ValidationException::withMessages(['message' => trans('global.update', ['attribute' => trans('student.payment.payment')])]);
        }

        $paymentType = $transaction->head;

        if ($paymentType == 'student_fee') {
            \DB::beginTransaction();

            (new PayOnlineFee)->studentFeePayment($student, $transaction);

            \DB::commit();
        }
    }
}
