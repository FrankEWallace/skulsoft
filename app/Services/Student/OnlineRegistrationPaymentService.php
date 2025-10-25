<?php

namespace App\Services\Student;

use App\Actions\Config\SetTeamWiseModuleConfig;
use App\Actions\Finance\CreateTransaction;
use App\Actions\Finance\GetPaymentGateway;
use App\Actions\SendMailTemplate;
use App\Actions\Student\PayOnlineFee;
use App\Contracts\Finance\PaymentGateway;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\TransactionType;
use App\Models\Finance\PaymentMethod;
use App\Models\Student\Admission;
use App\Models\Student\Registration;
use App\Models\Student\Student;
use App\Support\FormatCodeNumber;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OnlineRegistrationPaymentService
{
    use FormatCodeNumber;

    public function setFinanceConfig(int $teamId, string $module = 'finance')
    {
        (new SetTeamWiseModuleConfig)->execute($teamId, $module);
    }

    public function preRequisite(Request $request, Registration $registration)
    {
        $team = $registration->period->team;

        $paymentGateways = (new GetPaymentGateway)->execute($team->id);

        return compact('paymentGateways');
    }

    public function initiate(Request $request, Registration $registration, PaymentGateway $paymentGateway): array
    {
        $period = $registration->period;

        if ($registration->fee->value <= 0) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        if ($registration->payment_status == PaymentStatus::PAID) {
            throw ValidationException::withMessages(['message' => trans('student.online_registration.fee_already_paid')]);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'gateway' => 'required|string',
        ]);

        if ($registration->fee->value != $request->amount) {
            throw ValidationException::withMessages(['message' => trans('student.online_registration.invalid_amount')]);
        }

        $paymentGateway->isEnabled();

        $paymentMethod = PaymentMethod::query()
            ->byTeam($period->team_id)
            ->where('is_payment_gateway', true)
            ->where('payment_gateway_name', $request->gateway)
            ->getOrFail(trans('finance.payment_method.payment_method'));

        $paymentGatewayAccount = null;

        $referenceNumber = strtoupper(date('ymd').Str::random(10));

        $request->merge([
            'team_id' => $period->team_id,
            'period_id' => $period->id,
            'transactionable_type' => 'Registration',
            'transactionable_id' => $registration->id,
            'head' => 'registration_fee',
            'type' => TransactionType::RECEIPT->value,
            'is_online' => true,
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
        ]);

        $transaction = (new CreateTransaction)->execute($request->all());
        $transaction->save();

        $student = new Student;
        $student->code_number = $registration->code_number;
        $student->name = $registration->contact->name;
        $student->course_name = $registration->course->name;
        $student->batch_name = $registration->period->name;
        $student->email = $registration->contact->email;

        return $paymentGateway->initiatePayment($request, $student, $transaction);
    }

    private function codeNumber(Registration $registration)
    {
        $numberPrefix = config('config.student.admission_number_prefix');
        $numberSuffix = config('config.student.admission_number_suffix');
        $digit = config('config.student.admission_number_digit', 0);

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $numberFormat = $this->preFormatForDate($numberFormat);

        $numberFormat = $this->preFormatForAcademicCourse($registration->course_id, $numberFormat);

        $codeNumber = (int) Admission::query()
            ->byTeam($registration->period->team_id)
            ->whereNumberFormat($numberFormat)
            ->max('number') + 1;

        return $this->getCodeNumber(number: $codeNumber, digit: $digit, format: $numberFormat);
    }

    public function makePayment(Request $request, Registration $registration, PaymentGateway $paymentGateway)
    {
        \DB::beginTransaction();

        $transaction = $paymentGateway->confirmPayment($request);

        $registration->payment_status = PaymentStatus::PAID;
        $registration->save();

        (new PayOnlineFee)->registrationFeePayment($registration, $transaction);

        \DB::commit();

        (new SendMailTemplate)->execute(
            email: $registration->contact->email,
            code: 'online-registration-fee-payment-success',
            variables: [
                'name' => $registration->contact->name,
                'application_number' => $registration->getMeta('application_number'),
                'program' => $registration->course->division?->program?->name,
                'period' => $registration->period->name,
                'course' => $registration->course->name,
                'amount' => $transaction->amount->formatted,
                'payment_date' => $transaction->processed_at->formatted,
                'reference_number' => Arr::get($transaction->payment_gateway, 'reference_number'),
            ]
        );

        return $transaction;
    }

    public function failPayment(Request $request, Registration $registration, PaymentGateway $paymentGateway)
    {
        $transaction = $paymentGateway->failPayment($request);

        $transaction = app(Pipeline::class)
            ->send($transaction)
            ->through([
                // SendFailureNotification::class
            ])
            ->thenReturn();

        (new SendMailTemplate)->execute(
            email: $registration->contact->email,
            code: 'online-registration-fee-payment-failure',
            variables: [
                'name' => $registration->contact->name,
                'application_number' => $registration->getMeta('application_number'),
                'program' => $registration->course->division?->program?->name,
                'period' => $registration->period->name,
                'course' => $registration->course->name,
                'amount' => $transaction->amount->formatted,
                'payment_date' => \Cal::dateTime(now()->toDateTimeString())->formatted,
                'reference_number' => Arr::get($transaction->payment_gateway, 'reference_number'),
            ]
        );
    }

    // public function updatePaymentStatus(Request $request, Registration $registration, string $uuid)
    // {
    //     $transaction = Transaction::query()
    //         ->where('transactionable_type', 'Student')
    //         ->where('transactionable_id', $student->id)
    //         ->where('uuid', $uuid)
    //         ->whereNull('processed_at')
    //         ->where('is_online', true)
    //         ->firstOrFail();

    //     $referenceNumber = Arr::get($transaction->payment_gateway, 'reference_number');
    //     $gatewayName = Arr::get($transaction->payment_gateway, 'name');

    //     if (! in_array($gatewayName, ['billdesk', 'ccavenue'])) {
    //         throw ValidationException::withMessages(['message' => trans('finance.could_not_update_payment_status')]);
    //     }

    //     \Artisan::call($gatewayName.':status', [
    //         'refnum' => $referenceNumber,
    //     ]);
    // }
}
