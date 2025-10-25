<?php

namespace App\Actions\Student;

use App\Models\Finance\FeeGroup;
use App\Models\Student\Fee;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class GetPayableInstallment
{
    public function execute(Request $request, Student $student): Collection
    {
        $date = $request->date ?? today()->toDateString();

        if ($request->fee_group) {
            $studentFees = $this->getPayableInstallmentFromFeeGroup($request, $student);
        } else {
            $studentFees = $this->getPayableInstallmentFromFeeInstallment($request, $student);
        }

        $balance = 0;
        $totalLateFee = 0;
        $payableStudentFeeIds = [];
        foreach ($studentFees as $studentFee) {
            $totalLateFee += $studentFee->calculateLateFeeAmount($date)->value;

            $studentFeeBalance = $studentFee->getBalance($date)->value;

            if ($studentFeeBalance > 0) {
                $payableStudentFeeIds[] = $studentFee->id;
            }

            $balance += $studentFeeBalance;
        }

        if (! $request->has('late_fee')) {
            $request->merge(['late_fee' => $totalLateFee]);
        }

        $hasCustomLateFee = false;
        if ($request->late_fee != $totalLateFee) {

            if (! auth()->user()->can('fee:customize-late-fee')) {
                throw ValidationException::withMessages(['message' => trans('student.fee.could_not_customize_late_fee')]);
            }

            $hasCustomLateFee = true;
            $balance = $balance - $totalLateFee + $request->late_fee;
        }

        $payableStudentFees = $studentFees->whereIn('id', $payableStudentFeeIds);

        if (! $payableStudentFees->count()) {
            throw ValidationException::withMessages(['message' => trans('student.fee.no_payable_fee')]);
        }

        if ($hasCustomLateFee && $balance > $request->amount) {
            throw ValidationException::withMessages(['message' => trans('student.fee.partial_payment_not_allowed_for_custom_late_fee')]);
        }

        $lateFeeBalance = $balance - $request->amount;
        if ($request->late_fee && $lateFeeBalance > 0 && $lateFeeBalance < $request->late_fee) {
            throw ValidationException::withMessages(['message' => trans('student.fee.partial_payment_not_allowed_for_late_fee')]);
        }

        if ($hasCustomLateFee) {
            foreach ($payableStudentFees as $index => $studentFee) {
                $studentFee->setMeta([
                    'custom_late_fee' => true,
                    'late_fee_amount' => $index == 0 ? $request->late_fee : 0,
                    'original_late_fee_amount' => $totalLateFee,
                ]);
            }
        }

        $totalAdditionalCharge = array_sum(array_column($request->additional_charges ?? [], 'amount'));
        $totalAdditionalDiscount = array_sum(array_column($request->additional_discounts ?? [], 'amount'));

        $totalAdditionalFee = $totalAdditionalCharge - $totalAdditionalDiscount;

        if ($totalAdditionalCharge > 0 || $totalAdditionalDiscount > 0) {
            $firstStudentFee = $payableStudentFees->sortBy('final_due_date.value')->first();

            if ($hasCustomLateFee) {
                $lateFeeAmount = $firstStudentFee->getMeta('late_fee_amount') ?? 0;
            } else {
                $lateFeeAmount = $firstStudentFee->calculateLateFeeAmount($date)->value;
            }

            $studentFeeBalance = $firstStudentFee->total->value + $lateFeeAmount - $firstStudentFee->paid->value + $totalAdditionalFee;

            // Allow zero fee payment if discount is zero
            // if ($studentFeeBalance <= 0) {
            //     throw ValidationException::withMessages(['message' => trans('student.fee.could_not_pay_lt_zero')]);
            // }

            // But if additional charge is not zero then don't allow zero fee payment
            if ($studentFeeBalance <= 0 && $totalAdditionalCharge > 0) {
                throw ValidationException::withMessages(['message' => trans('student.fee.could_not_pay_lt_zero')]);
            }

            if ($request->amount > 0 && $request->amount < $studentFeeBalance) {
                throw ValidationException::withMessages(['message' => trans('student.fee.partial_payment_not_allowed_for_additional_fee')]);
            }

            if ($request->amount == 0 && $totalAdditionalCharge > 0) {
                throw ValidationException::withMessages(['message' => trans('student.fee.amount_cannot_be_zero')]);
            }

            $balance = $balance + $totalAdditionalCharge - $totalAdditionalDiscount;
        }

        $this->validateBalance($balance, $request->amount);

        $this->validatePartialPayment($balance, $request->amount);

        return $payableStudentFees;
    }

    private function getPayableInstallmentFromFeeGroup(Request $request, Student $student): Collection
    {
        $feeGroup = FeeGroup::query()
            ->byPeriod($student->period_id)
            ->whereUuid($request->fee_group)
            ->getOrFail(trans('finance.fee_group.fee_group'), 'message');

        return Fee::query()
            ->select('student_fees.*', \DB::raw('COALESCE(student_fees.due_date, fee_installments.due_date) as final_due_date'))
            ->join('fee_installments', 'fee_installments.id', '=', 'student_fees.fee_installment_id')
            ->whereStudentId($student->id)
            ->where('fee_installments.fee_group_id', $feeGroup->id)
            ->orderBy('final_due_date', 'asc')
            ->get();
    }

    private function getPayableInstallmentFromFeeInstallment(Request $request, Student $student): Collection
    {
        $date = $request->date ?? today()->toDateString();

        $studentFees = Fee::query()
            ->whereStudentId($student->id)
            ->when($request->fee_installment, function ($q) use ($request) {
                $q->whereUuid($request->fee_installment);
            })
            ->when($request->fee_installments, function ($q) use ($request) {
                $q->whereIn('uuid', $request->fee_installments);
            })
            ->get();

        $studentFee = $studentFees->first();
        $installmentDueDate = $studentFee?->getDueDate()?->value;

        if (! $installmentDueDate) {
            return $studentFees;
        }

        if (config('config.student.allow_flexible_installment_payment') && auth()->check() && auth()->user()->can('fee:flexible-installment-payment')) {
            return $studentFees;
        }

        $previousInstallmentDue = Fee::query()
            ->join('fee_installments', 'fee_installments.id', '=', 'student_fees.fee_installment_id')
            ->whereStudentId($student->id)
            ->where('student_fees.id', '!=', $studentFee?->id)
            ->where('fee_installments.fee_group_id', $studentFee?->installment?->fee_group_id)
            ->where(function ($q) {
                $q->whereNull('fee_installments.meta->is_custom')
                    ->orWhere('fee_installments.meta->is_custom', '!=', true);
            })
            ->where(\DB::raw('COALESCE(student_fees.due_date, fee_installments.due_date)'), '<', $installmentDueDate)
            ->where(\DB::raw('total - paid'), '>', 0)
            ->first();

        if ($previousInstallmentDue) {
            throw ValidationException::withMessages(['message' => trans('student.fee.previous_installment_due')]);
        }

        return $studentFees;
    }

    private function validateBalance(float $balance, float $amount): void
    {
        if ($balance >= $amount) {
            return;
        }

        throw ValidationException::withMessages(['message' => trans('finance.fee.amount_gt_balance', ['amount' => \Price::from($amount)->formatted, 'balance' => \Price::from($balance)->formatted])]);
    }

    private function validatePartialPayment(float $balance, float $amount): void
    {
        if (! empty(auth()->user()) && auth()->user()?->can('fee:partial-payment')) {
            return;
        }

        if ($balance == $amount) {
            return;
        }

        throw ValidationException::withMessages(['message' => trans('student.fee.could_not_make_partial_payment')]);
    }
}
