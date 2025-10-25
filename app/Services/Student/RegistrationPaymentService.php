<?php

namespace App\Services\Student;

use App\Actions\Finance\CancelTransaction;
use App\Actions\Finance\CreateTransaction;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\TransactionType;
use App\Enums\Student\RegistrationStatus;
use App\Http\Resources\Finance\LedgerResource;
use App\Http\Resources\Finance\PaymentMethodResource;
use App\Models\Finance\Ledger;
use App\Models\Finance\PaymentMethod;
use App\Models\Finance\Transaction;
use App\Models\Student\Registration;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RegistrationPaymentService
{
    public function preRequisite(Request $request): array
    {
        $paymentMethods = PaymentMethodResource::collection(PaymentMethod::query()
            ->byTeam()
            ->where('is_payment_gateway', false)
            ->get());

        $ledgers = LedgerResource::collection(Ledger::query()
            ->byTeam()
            ->subType('primary')
            ->get()
        );

        return compact('paymentMethods', 'ledgers');
    }

    public function skipPayment(Request $request, Registration $registration)
    {
        if ($registration->fee->value <= 0) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        if ($registration->payment_status != PaymentStatus::UNPAID) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        \DB::beginTransaction();

        $registration->fee = 0;
        $registration->payment_status = PaymentStatus::NA;
        $registration->save();

        \DB::commit();
    }

    public function payment(Request $request, Registration $registration)
    {
        if ($registration->getMeta('payment_due_date') && $request->date > $registration->getMeta('payment_due_date')) {
            throw ValidationException::withMessages(['date' => trans('student.registration.cannot_pay_after_due_date', ['attribute' => \Cal::date($registration->getMeta('payment_due_date'))->formatted])]);
        }

        $request->merge([
            'period_id' => $registration->period_id,
            'transactionable_type' => 'Registration',
            'transactionable_id' => $registration->id,
            'head' => 'registration_fee',
            'type' => TransactionType::RECEIPT->value,
        ]);

        \DB::beginTransaction();

        $params = $request->all();
        $params['course_id'] = $registration->course_id;
        $params['payments'] = [
            [
                'ledger_id' => $request->ledger?->id,
                'amount' => $request->amount,
                'payment_method_id' => $request->payment_method_id,
                'payment_method_details' => $request->payment_method_details,
            ],
        ];

        (new CreateTransaction)->execute($params);

        $paidAmount = Transaction::query()
            ->whereTransactionableId($registration->id)
            ->whereTransactionableType('Registration')
            ->whereHead('registration_fee')
            ->whereNull('cancelled_at')
            ->whereNull('rejected_at')
            ->where(function ($q) {
                $q->where('is_online', false)
                    ->orWhere(function ($q) {
                        $q->where('is_online', true)
                            ->whereNotNull('processed_at');
                    });
            })
            ->sum('amount');

        $paymentStatus = PaymentStatus::PAID;

        if ($paidAmount < $registration->fee->value) {
            $paymentStatus = PaymentStatus::PARTIALLY_PAID;
        }

        $registration->payment_status = $paymentStatus;
        $registration->save();

        \DB::commit();
    }

    public function getPayment(Registration $registration, string $uuid)
    {
        $transaction = $registration->transactions->firstWhere('uuid', $uuid);

        if (! $transaction) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        return $transaction;
    }

    public function cancelPayment(Request $request, Registration $registration, $uuid)
    {
        if ($registration->status != RegistrationStatus::PENDING) {
            throw ValidationException::withMessages(['message' => trans('student.registration.could_not_delete_transaction_if_processed')]);
        }

        if (! in_array($registration->payment_status, [PaymentStatus::PARTIALLY_PAID, PaymentStatus::PAID])) {
            throw ValidationException::withMessages(['message' => trans('finance.fee.not_paid')]);
        }

        $transaction = $registration->transactions->firstWhere('uuid', $uuid);

        if (! $transaction) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        \DB::beginTransaction();

        (new CancelTransaction)->execute($request, $transaction);

        $registration->payment_status = PaymentStatus::UNPAID;
        $registration->save();

        \DB::commit();
    }
}
