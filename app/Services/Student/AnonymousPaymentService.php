<?php

namespace App\Services\Student;

use App\Actions\Finance\GetPaymentGateway;
use App\Models\Finance\Transaction;
use App\Models\Student\Student;
use App\Models\TempStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class AnonymousPaymentService
{
    public function getDetail(Request $request)
    {
        $tempStorage = TempStorage::query()
            ->whereUuid($request->uuid)
            ->firstOrFail();

        if (app()->environment('production') && $tempStorage->created_at->diffInMinutes(now()) > 10) {
            return abort(398, trans('student.payment.link_expired'));
        }

        if ($tempStorage->type != 'student_fee_payment') {
            return abort(398, trans('general.errors.invalid_action'));
        }

        $student = Student::query()
            ->whereUuid($tempStorage->getValue('student'))
            ->firstOrFail();

        $amount = $tempStorage->getValue('amount');

        if (! is_numeric($amount) || $amount <= 0) {
            return abort(398, trans('general.errors.invalid_input'));
        }

        $transaction = Transaction::query()
            ->where('meta->temp_payment_uuid', $tempStorage->uuid)
            ->where('is_online', true)
            ->whereNotNull('processed_at')
            ->first();

        $amount = \Price::from($amount);

        $student->load('admission', 'contact', 'batch.course', 'period.team');

        $paymentGateways = (new GetPaymentGateway)->execute($student->period?->team_id);

        return [
            'student' => [
                'uuid' => $student->uuid,
                'code_number' => $student->admission->code_number,
                'name' => $student->contact->name,
                'batch_name' => $student->batch->name,
                'course_name' => $student->batch->course->name,
                'period_name' => $student->period->name,
            ],
            'transaction' => [
                'has_completed' => $transaction ? true : false,
                'reference_number' => Arr::get($transaction?->payment_gateway, 'reference_number'),
            ],
            'date' => \Cal::date(today()->format('d-m-Y')),
            'amount' => $amount,
            'fee_group' => [
                'uuid' => $tempStorage->getValue('fee_group'),
                'balance' => $amount,
            ],
            'fee_head' => [
                'uuid' => $tempStorage->getValue('fee_head'),
                'balance' => $amount,
            ],
            'fee_installment' => [
                'uuid' => $tempStorage->getValue('fee_installment'),
                'balance' => $amount,
            ],
            'payment_gateways' => $paymentGateways,
        ];
    }
}
