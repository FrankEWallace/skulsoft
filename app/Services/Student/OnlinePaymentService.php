<?php

namespace App\Services\Student;

use App\Actions\Config\SetTeamWiseModuleConfig;
use App\Actions\Finance\CreateTransaction;
use App\Actions\PaymentGateway\UpdateBillplzPayment;
use App\Actions\Student\GetPayableInstallment;
use App\Actions\Student\PayOnlineFee;
use App\Contracts\Finance\PaymentGateway;
use App\Enums\Finance\TransactionType;
use App\Models\Finance\FeeGroup;
use App\Models\Finance\PaymentMethod;
use App\Models\Finance\Transaction;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OnlinePaymentService
{
    public function setFinanceConfig(int $teamId, string $module = 'finance')
    {
        (new SetTeamWiseModuleConfig)->execute($teamId, $module);
    }

    public function initiate(Request $request, Student $student, PaymentGateway $paymentGateway): array
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'amount' => 'required|numeric|min:0.01',
            'gateway' => 'required|string',
        ]);

        $paymentGateway->isEnabled();

        $paymentMethod = PaymentMethod::query()
            ->byTeam($student->team_id)
            ->where('is_payment_gateway', true)
            ->where('payment_gateway_name', $request->gateway)
            ->getOrFail(trans('finance.payment_method.payment_method'));

        $studentFees = (new GetPayableInstallment)->execute($request, $student);

        // get online payment account

        $batch = $student->batch;
        $course = $batch->course;
        $division = $course->division;

        $paymentGatewayAccount = null;

        $feeGroup = FeeGroup::query()
            ->byPeriod($student->period_id)
            ->findOrFail($studentFees->first()->installment->fee_group_id);

        if ($feeGroup->getMeta('pg_account')) {
            $paymentGatewayAccount = $feeGroup->getMeta('pg_account');
        } elseif ($division->getMeta('pg_account')) {
            $paymentGatewayAccount = $division->getMeta('pg_account');
        } elseif ($course->getMeta('pg_account')) {
            $paymentGatewayAccount = $course->getMeta('pg_account');
        } elseif ($batch->getMeta('pg_account')) {
            $paymentGatewayAccount = $batch->getMeta('pg_account');
        }

        $referenceNumber = strtoupper(date('ymd').Str::random(10));

        $request->merge([
            'team_id' => $student->team_id,
            'period_id' => $student->period_id,
            'transactionable_type' => 'Student',
            'transactionable_id' => $student->id,
            'head' => 'student_fee',
            'type' => TransactionType::RECEIPT->value,
            'is_online' => true,
            'payment_method_code' => $paymentMethod->code,
            'payments' => [
                [
                    'amount' => $request->amount,
                    'payment_method_id' => $paymentMethod->id,
                    'payment_method_details' => [
                        'reference_number' => $referenceNumber,
                    ],
                ],
            ],
            'payment_gateway' => [
                'reference_number' => $referenceNumber,
                'name' => $paymentGateway->getName(),
                'pg_account' => $paymentGatewayAccount,
                'version' => $paymentGateway->getVersion(),
            ],
            'fee_group_name' => $feeGroup->name,
        ]);

        $transaction = (new CreateTransaction)->execute($request->all());

        $transaction->setMeta([
            'student_fee_ids' => $studentFees->pluck('id')->all(),
        ]);

        if ($request->temp_payment_uuid) {
            $transaction->setMeta([
                'temp_payment_uuid' => $request->temp_payment_uuid,
            ]);
        }

        $transaction->save();

        return $paymentGateway->initiatePayment($request, $student, $transaction);
    }

    public function makePayment(Request $request, Student $student, PaymentGateway $paymentGateway)
    {
        \DB::beginTransaction();

        $transaction = $paymentGateway->confirmPayment($request);

        if ($transaction->processed_at->value) {
            throw ValidationException::withMessages(['message' => trans('student.payment.already_processed')]);
        }

        (new PayOnlineFee)->studentFeePayment($student, $transaction);

        \DB::commit();

        return $transaction;
    }

    public function failPayment(Request $request, Student $student, PaymentGateway $paymentGateway)
    {
        $transaction = $paymentGateway->failPayment($request);

        $transaction = app(Pipeline::class)
            ->send($transaction)
            ->through([
                // SendFailureNotification::class
            ])
            ->thenReturn();

        return $transaction;
    }

    public function updatePaymentStatus(Request $request, Student $student, string $uuid)
    {
        $transaction = Transaction::query()
            ->where('transactionable_type', 'Student')
            ->where('transactionable_id', $student->id)
            ->where('uuid', $uuid)
            ->whereNull('processed_at')
            ->where('is_online', true)
            ->firstOrFail();

        $referenceNumber = Arr::get($transaction->payment_gateway, 'reference_number');
        $gatewayName = Arr::get($transaction->payment_gateway, 'name');

        if (! in_array($gatewayName, ['billdesk', 'ccavenue', 'billplz'])) {
            throw ValidationException::withMessages(['message' => trans('finance.could_not_update_payment_status')]);
        }

        if ($gatewayName == 'billplz') {
            (new UpdateBillplzPayment)->execute($request, $student, $transaction);

            return;
        }

        \Artisan::call($gatewayName.':status', [
            'refnum' => $referenceNumber,
        ]);
    }
}
