<?php

namespace App\Services\Library;

use App\Actions\Student\CreateCustomFeeHead;
use App\Enums\Finance\DefaultCustomFeeType;
use App\Enums\Library\HoldStatus;
use App\Enums\Library\ReturnStatus;
use App\Models\Finance\FeeHead;
use App\Models\Library\Transaction;
use App\Models\Library\TransactionRecord;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TransactionActionService
{
    public function returnBook(Request $request, Transaction $transaction): void
    {
        if ($request->return_date < $transaction->issue_date->value) {
            throw ValidationException::withMessages(['return_date' => trans('validation.after_or_equal', ['attribute' => trans('library.transaction.props.return_date'), 'date' => $transaction->issue_date->formatted])]);
        }

        $transactionRecord = TransactionRecord::query()
            ->whereBookTransactionId($transaction->id)
            ->whereHas('copy', function ($query) use ($request) {
                $query->whereNumber($request->number);
            })
            ->where('return_date', null)
            ->firstOr(function () {
                throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('library.book.props.number')])]);
            });

        if ($request->library_charge > 0 && $transaction->transactionable_type != 'Student') {
            throw ValidationException::withMessages(['library_charge' => trans('general.errors.invalid_input')]);
        }

        $student = null;
        if ($request->library_charge > 0) {
            $feeHead = FeeHead::query()
                ->byPeriod()
                ->where('type', DefaultCustomFeeType::LIBRARY_CHARGE)
                ->first();

            if (! $feeHead) {
                throw ValidationException::withMessages(['message' => trans('library.transaction.could_not_find_library_charge_fee_head')]);
            }

            $student = Student::query()
                ->whereId($transaction->transactionable_id)
                ->firstOrFail();
        }

        \DB::beginTransaction();

        if ($request->library_charge > 0) {
            (new CreateCustomFeeHead)->execute($student, [
                'fee_head_id' => $feeHead->id,
                'amount' => $request->library_charge,
                'due_date' => today()->toDateString(),
                'remarks' => '',
                'meta' => [
                    'code_number' => $transaction->code_number,
                    'transaction_record_uuid' => $transactionRecord->uuid,
                ],
            ]);
        }

        $transactionRecord->return_date = $request->return_date;
        $transactionRecord->charges = [
            'fee' => DefaultCustomFeeType::LIBRARY_CHARGE->value,
            'amount' => $request->library_charge,
        ];
        $transactionRecord->return_status = $request->return_status;
        $transactionRecord->condition_id = $request->condition_id;
        $transactionRecord->remarks = $request->remarks;
        $transactionRecord->save();

        $bookCopy = $transactionRecord->copy;

        if (in_array($request->return_status, [ReturnStatus::DAMAGED->value, ReturnStatus::LOST->value])) {
            $bookCopy->hold_status = match ($request->return_status) {
                ReturnStatus::DAMAGED->value => HoldStatus::DAMAGED->value,
                ReturnStatus::LOST->value => HoldStatus::LOST->value,
            };
        }

        $bookCopy->condition_id = $request->condition_id;
        $bookCopy->save();

        \DB::commit();
    }
}
