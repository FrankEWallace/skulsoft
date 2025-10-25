<?php

namespace App\Services\PaymentGateway;

use App\Actions\Student\PayOnlineFee;
use App\Helpers\SysHelper;
use App\Models\Config\Config;
use App\Models\Finance\Transaction;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class PayzoneService
{
    public function getResponse(Request $request)
    {
        $referenceNumber = $request->id;

        $transaction = Transaction::query()
            ->with('period')
            ->where('payment_gateway->reference_number', $referenceNumber)
            ->whereNull('processed_at')
            ->first();

        if (! $transaction) {
            abort(404);
        }

        $config = Config::query()
            ->where('team_id', $transaction->period->team_id)
            ->whereName('finance')
            ->first();

        $notificationKey = Arr::get($config->value, 'payzone_notification_key');

        $input = file_get_contents('php://input');

        $signature = hash_hmac('sha256', $input, $notificationKey);

        if (strcasecmp($signature, $request->header('X-callback-signature')) > 0) {
            return response()->error([]);
        }

        $transaction->payment_gateway = array_merge($transaction->payment_gateway, [
            'tracking_id' => $request->internalId,
        ]);
        $transaction->save();

        $properties = $request->properties;
        $paymentType = Arr::get($properties, 'param1');
        $transactionUuid = Arr::get($properties, 'param2');

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
            return response()->error([]);
            // return view('messages.alert', [
            //     'message' => trans('finance.id_mismatch', ['attribute' => $referenceNumber]),
            //     'url' => $url,
            //     'actionText' => $actionText,
            // ]);
        }

        if ($request->status != 'CHARGED') {
            return response()->error([]);
            // return view('messages.alert', [
            //     'message' => trans('finance.transaction_failed', ['attribute' => $referenceNumber]),
            //     'url' => $url,
            //     'actionText' => $actionText,
            // ]);
        }

        // if ($amount != $transaction->amount->value) {
        //     return view('messages.alert', [
        //         'message' => trans('finance.amount_mismatch', ['attribute' => $referenceNumber]),
        //         'url' => $url,
        //         'actionText' => $actionText,
        //     ]);
        // }

        if ($paymentType == 'student_fee') {
            if ($transaction->user_id && empty(auth()->user())) {
                \Auth::loginUsingId($transaction->user_id);
                SysHelper::setTeam($transaction->period->team_id);
            }

            \DB::beginTransaction();

            (new PayOnlineFee)->studentFeePayment($student, $transaction);

            \DB::commit();

            return response()->ok([]);
            // return view('messages.alert', [
            //     'message' => trans('finance.payment_succeed', ['amount' => $transaction->amount->formatted, 'attribute' => $referenceNumber]),
            //     'type' => 'success',
            //     'url' => $url,
            //     'actionText' => $actionText,
            // ]);
        }

        return response()->error([]);

        // return view('messages.alert', [
        //     'message' => trans('general.errors.invalid_operation'),
        //     'url' => $url,
        //     'actionText' => $actionText,
        // ]);
    }

    public function cancel(Request $request)
    {
        $referenceNumber = $request->id;

        return view('messages.alert', [
            'message' => trans('finance.payment_cancelled', ['attribute' => $referenceNumber]),
            'url' => route('app'),
            'actionText' => trans('global.go_to', ['attribute' => trans('dashboard.dashboard')]),
        ]);
    }
}
