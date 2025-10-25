<?php

namespace App\Services\Student;

use App\Actions\Student\GetPayableInstallment;
use App\Actions\Student\GetStudentFees;
use App\Enums\Finance\BankTransferStatus;
use App\Models\Finance\BankTransfer;
use App\Models\Student\Student;
use App\Support\FormatCodeNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class BankTransferService
{
    use FormatCodeNumber;

    private function codeNumber(): array
    {
        $numberPrefix = config('config.finance.bank_transfer_number_prefix');
        $numberSuffix = config('config.finance.bank_transfer_number_suffix');
        $digit = config('config.finance.bank_transfer_number_digit', 0);

        $numberFormat = $numberPrefix.'%NUMBER%'.$numberSuffix;

        $numberFormat = $this->preFormatForDate($numberFormat);

        $codeNumber = (int) BankTransfer::query()
            ->byTeam()
            ->whereNumberFormat($numberFormat)
            ->max('number') + 1;

        return $this->getCodeNumber(number: $codeNumber, digit: $digit, format: $numberFormat);
    }

    public function bankTransfer(Request $request, Student $student)
    {
        (new GetStudentFees)->validatePreviousDue($student);

        $studentFees = (new GetPayableInstallment)->execute($request, $student);

        $existingBankTransfer = BankTransfer::query()
            ->where('model_type', 'Student')
            ->where('model_id', $student->id)
            ->where('status', BankTransferStatus::PENDING)
            ->get();

        foreach ($existingBankTransfer as $bankTransfer) {
            if ($bankTransfer->getMeta('student_fees') == $studentFees->pluck('uuid')->all()) {
                throw ValidationException::withMessages([
                    'message' => trans('student.fee.bank_transfer_already_uploaded'),
                ]);
            }
        }

        $codeNumberDetail = $this->codeNumber();

        $currency = config('config.system.currency');

        $bankTransfer = BankTransfer::create([
            'number_format' => Arr::get($codeNumberDetail, 'number_format'),
            'number' => Arr::get($codeNumberDetail, 'number'),
            'code_number' => Arr::get($codeNumberDetail, 'code_number'),
            'model_type' => 'Student',
            'model_id' => $student->id,
            'amount' => $request->amount,
            'currency' => $currency,
            'date' => today()->toDateString(),
            'remarks' => $request->remarks,
            'requester_id' => auth()->user()->id,
            'period_id' => $student->period_id,
            'status' => BankTransferStatus::PENDING,
            'meta' => [
                'transaction_params' => [
                    'fee_group' => $request->fee_group,
                    'fee_head' => $request->fee_head,
                    'fee_installment' => $request->fee_installment,
                ],
                'student_fees' => $studentFees->pluck('uuid')->all(),
            ],
        ]);

        $bankTransfer->addMedia($request);

        return $bankTransfer;
    }

    public function bankTransferAction(Request $request, Student $student, string $uuid)
    {
        $bankTransfer = BankTransfer::query()
            ->where('model_type', 'Student')
            ->where('model_id', $student->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        if ($request->status == 'rejected') {
            $bankTransfer->update([
                'status' => $request->status,
                'approver_id' => auth()->user()->id,
                'comment' => $request->comment,
                'processed_at' => now()->toDateTimeString(),
            ]);

            return $bankTransfer;
        }

        $request->merge([
            'date' => $bankTransfer->date->value,
            'amount' => $bankTransfer->amount->value,
            'fee_group' => $bankTransfer->getMeta('transaction_params.fee_group'),
            'fee_head' => $bankTransfer->getMeta('transaction_params.fee_head'),
            'fee_installment' => $bankTransfer->getMeta('transaction_params.fee_installment'),
            'bank_transfer_id' => $bankTransfer->id,
        ]);

        (new PaymentService)->makePayment($request, $student);

        $bankTransfer->update([
            'status' => $request->status,
            'approver_id' => auth()->user()->id,
            'comment' => $request->comment,
            'processed_at' => now()->toDateTimeString(),
        ]);

        return $bankTransfer;
    }
}
