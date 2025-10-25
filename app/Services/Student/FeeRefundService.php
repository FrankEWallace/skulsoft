<?php

namespace App\Services\Student;

use App\Actions\Finance\CreateTransaction;
use App\Enums\Finance\TransactionType;
use App\Http\Resources\Finance\FeeHeadResource;
use App\Http\Resources\Finance\LedgerResource;
use App\Http\Resources\Finance\PaymentMethodResource;
use App\Models\Finance\FeeHead;
use App\Models\Finance\FeeRefund;
use App\Models\Finance\FeeRefundRecord;
use App\Models\Finance\Ledger;
use App\Models\Finance\PaymentMethod;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class FeeRefundService
{
    public function preRequisite(Request $request): array
    {
        $feeHeads = FeeHeadResource::collection(FeeHead::query()
            ->byPeriod()
            ->get());

        $paymentMethods = PaymentMethodResource::collection(PaymentMethod::query()
            ->byTeam()
            ->where('is_payment_gateway', false)
            ->get());

        $ledgers = LedgerResource::collection(Ledger::query()
            ->byTeam()
            ->subType('primary')
            ->get()
        );

        return compact('feeHeads', 'paymentMethods', 'ledgers');
    }

    public function findByUuidOrFail(Student $student, string $uuid): FeeRefund
    {
        return FeeRefund::query()
            ->withTransaction()
            ->where('student_id', $student->id)
            ->whereUuid($uuid)
            ->getOrFail(trans('student.fee_refund.fee_refund'));
    }

    public function create(Request $request, Student $student): FeeRefund
    {
        if (! $student->isStudying()) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        \DB::beginTransaction();

        $feeRefund = FeeRefund::forceCreate($this->formatParams($request, $student));

        $request->merge([
            'period_id' => $student->period_id,
            'transactionable_type' => 'Student',
            'transactionable_id' => $student->id,
            'head' => 'fee_refund',
            'amount' => $request->total,
            'type' => TransactionType::PAYMENT->value,
        ]);

        $params = $request->all();

        $params['records'] = [
            [
                'model_id' => $feeRefund->id,
                'model_type' => 'FeeRefund',
                'amount' => $request->total,
            ],
        ];

        $params['payments'] = [
            [
                'ledger_id' => $request->ledger?->id,
                'amount' => $request->total,
                'payment_method_id' => $request->payment_method_id,
                'payment_method_details' => $request->payment_method_details,
            ],
        ];

        $transaction = (new CreateTransaction)->execute($params);

        $this->updateRecords($request, $student, $feeRefund);

        \DB::commit();

        return $feeRefund;
    }

    private function formatParams(Request $request, Student $student, ?FeeRefund $feeRefund = null): array
    {
        $formatted = [
            'date' => $request->date,
            'total' => $request->total,
            'remarks' => $request->remarks,
        ];

        if (! $feeRefund) {
            $formatted['student_id'] = $student->id;
        }

        return $formatted;
    }

    private function updateRecords(Request $request, Student $student, FeeRefund $feeRefund)
    {
        $feeHeadIds = [];
        foreach ($request->records as $record) {
            $feeRefundRecord = FeeRefundRecord::firstOrCreate([
                'fee_refund_id' => $feeRefund->id,
                'fee_head_id' => Arr::get($record, 'fee_head_id'),
            ]);

            $feeHeadIds[] = Arr::get($record, 'fee_head_id');

            $feeRefundRecord->amount = Arr::get($record, 'amount');
            $feeRefundRecord->save();
        }

        FeeRefundRecord::query()
            ->whereFeeRefundId($feeRefund->id)
            ->whereNotIn('fee_head_id', $feeHeadIds)
            ->delete();
    }

    public function update(Request $request, Student $student, FeeRefund $feeRefund): void
    {
        if (! $student->isStudying()) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        \DB::beginTransaction();

        $feeRefund->forceFill($this->formatParams($request, $student, $feeRefund))->save();

        $this->updateRecords($request, $student, $feeRefund);

        \DB::commit();
    }

    public function deletable(Student $student, FeeRefund $feeRefund): void
    {
        throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
    }
}
