<?php

namespace App\Services\Student;

use App\Actions\Student\GetFeeInstallments;
use App\Actions\Student\GetHeadWiseFee;
use App\Enums\FamilyRelation;
use App\Enums\Finance\BankTransferStatus;
use App\Enums\Finance\PaymentStatus;
use App\Helpers\CalHelper;
use App\Http\Resources\MediaResource;
use App\Http\Resources\Student\BankTransferResource;
use App\Http\Resources\Student\FeeListResource;
use App\Http\Resources\Student\TransactionListForGuestResource;
use App\Http\Resources\Student\TransactionListResource;
use App\Models\Finance\BankTransfer;
use App\Models\Finance\FeeGroup;
use App\Models\Finance\Transaction;
use App\Models\Finance\TransactionPayment;
use App\Models\Finance\TransactionRecord;
use App\Models\Guardian;
use App\Models\Student\Fee;
use App\Models\Student\FeeRecord;
use App\Models\Student\Registration;
use App\Models\Student\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FeeListService
{
    public function fetchFee(Request $request, Student $student): array
    {
        $fees = Fee::query()
            ->with('installment', 'concession', 'transportCircle', 'records', 'records.head')
            ->whereStudentId($student->id)
            ->get();

        $studentFees = (new GetFeeInstallments)->execute($student, $fees);

        $feeGroups = FeeGroup::query()
            ->select('name', 'uuid', 'id')
            ->byPeriod($student->period_id)
            ->where(function ($q) {
                $q->whereNull('meta->is_custom')
                    ->orWhere('meta->is_custom', '!=', true);
            })
            ->get()
            ->transform(function ($feeGroup) use ($studentFees) {
                $installments = collect($studentFees)->filter(function ($studentFee) use ($feeGroup) {
                    return $studentFee['fee_group_uuid'] == $feeGroup->uuid;
                })->values();

                return [
                    'name' => $feeGroup->name,
                    'uuid' => $feeGroup->uuid,
                    'fees' => $installments,
                ];
            })
            ->filter(function ($feeGroup) {
                return count($feeGroup['fees']) > 0;
            })
            ->values();

        return compact('feeGroups');
    }

    public function listFee(Request $request, Student $student): array
    {
        if ($request->query('type', 'group') == 'head') {
            return $this->headWiseFee($request, $student);
        }

        return $this->groupWiseFee($request, $student);
    }

    public function getSiblingFees(Request $request, Student $student): array
    {
        $guardianContactIds = Guardian::query()
            ->select('contact_id')
            ->where('primary_contact_id', '=', $student->contact_id)
            ->whereIn('relation', [
                FamilyRelation::FATHER->value,
                FamilyRelation::MOTHER->value,
            ])
            ->pluck('contact_id')
            ->all();

        $siblings = Student::query()
            ->select('students.*', \DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ") as name'), 'contacts.first_name', 'contacts.last_name', 'contacts.contact_number', 'contacts.father_name', 'contacts.mother_name', 'contacts.photo', 'contacts.email', 'contacts.birth_date', 'contacts.gender', 'admissions.code_number', 'admissions.joining_date', 'batches.uuid as batch_uuid', 'batches.name as batch_name', 'courses.uuid as course_uuid', 'courses.name as course_name')
            ->byPeriod()
            ->join('contacts', function ($join) use ($guardianContactIds) {
                $join->on('students.contact_id', '=', 'contacts.id')
                    ->join('guardians', function ($join) use ($guardianContactIds) {
                        $join->on('primary_contact_id', '=', 'contacts.id')->whereIn('guardians.contact_id', $guardianContactIds);
                    });
            })
            ->join('admissions', function ($join) {
                $join->on('students.admission_id', '=', 'admissions.id');
            })
            ->join('batches', function ($join) {
                $join->on('students.batch_id', '=', 'batches.id')
                    ->leftJoin('courses', function ($join) {
                        $join->on('batches.course_id', '=', 'courses.id');
                    });
            })
            ->distinct()
            ->where('students.uuid', '!=', $student->uuid)
            ->where('contacts.id', '!=', $student->contact_id)
            ->whereNull('students.cancelled_at')->where(function ($q) {
                $q->whereNull('admissions.leaving_date')
                    ->orWhere('admissions.leaving_date', '>', today()->toDateString());
            })
            ->get();

        $student->load('fees');

        $siblings = $siblings->map(function ($sibling) {
            return [
                'uuid' => $sibling->uuid,
                'name' => $sibling->name,
                'course_name' => $sibling->course_name,
                'batch_name' => $sibling->batch_name,
                'code_number' => $sibling->code_number,
                'photo_url' => url($sibling->photo_url),
                'fee_summary' => $sibling->getFeeSummary(),
            ];
        });

        return compact('siblings');
    }

    public function groupWiseFee(Request $request, Student $student): array
    {
        $date = $request->query('date');

        if (! CalHelper::validateDate($date)) {
            $date = Carbon::parse(CalHelper::toDateTime(now()->toDateTimeString()))->toDateString();
        }

        $currentDate = Carbon::parse(CalHelper::toDateTime(now()->toDateTimeString()))->toDateString();

        if (! auth()->check()) {
            $date = $currentDate;
        } elseif (auth()->user()->hasAnyRole(['student', 'guardian']) && $date != $currentDate) {
            $date = $currentDate;
        }

        $fees = Fee::query()
            ->with('installment', 'installment.group', 'concession', 'transportCircle', 'records', 'records.head')
            ->whereStudentId($student->id)
            ->get();

        if (! $fees->count()) {
            throw ValidationException::withMessages(['message' => trans('student.fee.set_fee_info')]);
        }

        $bankTransfers = BankTransfer::query()
            ->with('requester', 'approver', 'media')
            ->where('model_type', 'Student')
            ->where('model_id', $student->id)
            ->get();

        foreach ($bankTransfers as $bankTransfer) {
            $feeDetails = [];
            foreach ($fees->whereIn('uuid', $bankTransfer->getMeta('student_fees', [])) as $fee) {
                $feeDetails[] = $fee->installment->group->name.' '.$fee->installment->title;
            }

            $bankTransfer->fee_details = $feeDetails;
        }

        foreach ($fees as $fee) {
            $feeBankTransfers = $bankTransfers->filter(function ($bankTransfer) use ($fee) {
                return in_array($fee->uuid, $bankTransfer->getMeta('student_fees', []));
            })->values();

            $fee->bank_transfers = $feeBankTransfers->map(function ($feeBankTransfer) {
                return [
                    'uuid' => $feeBankTransfer->uuid,
                    'code_number' => $feeBankTransfer->code_number,
                    'amount' => $feeBankTransfer->amount,
                    'date' => $feeBankTransfer->date,
                    'status' => BankTransferStatus::getDetail($feeBankTransfer->status),
                    'fee_details' => $feeBankTransfer->fee_details,
                    'remarks' => $feeBankTransfer->remarks,
                    'comment' => $feeBankTransfer->comment,
                    'media' => MediaResource::collection($feeBankTransfer->media),
                ];
            });
        }

        $request->merge([
            'has_bank_transfer' => true,
        ]);

        $transactions = Transaction::query()
            ->select('transactions.*', 'users.name as user_name')
            ->leftJoin('users', 'transactions.user_id', '=', 'users.id')
            ->where('transactions.head', '=', 'student_fee')
            ->where('transactions.transactionable_type', '=', 'Student')
            ->where('transactions.transactionable_id', '=', $student->id)
            ->get();

        $transactionRecords = TransactionRecord::query()
            ->select('transaction_records.*', 'student_fees.uuid as student_fee_uuid')
            ->join('student_fees', 'transaction_records.model_id', '=', 'student_fees.id')
            ->whereIn('transaction_records.transaction_id', $transactions->pluck('id')->all())
            ->get();

        $payments = TransactionPayment::query()
            ->select('transaction_payments.*', 'ledgers.name as ledger_name', 'ledgers.uuid as ledger_uuid')
            ->with('method')
            ->leftJoin('ledgers', 'transaction_payments.ledger_id', '=', 'ledgers.id')
            ->whereIn('transaction_id', $transactions->pluck('id')->all())
            ->get();

        $request->merge([
            'validate_clearance' => true,
        ]);

        $transactions = $transactions->map(function ($transaction) use ($payments, $transactionRecords) {
            $transaction->records = $transactionRecords->where('transaction_id', $transaction->id);
            $transaction->payments = $payments->where('transaction_id', $transaction->id);

            $pendingClearance = $transaction->payments->contains(function ($payment) {
                return $payment->method->getConfig('has_clearing_date') && empty(Arr::get($payment->details, 'clearing_date'));
            });

            if ($transaction->cancelled_at->value || $transaction->rejected_at->value) {
                $pendingClearance = false;
            }

            $transaction->pending_clearance = $pendingClearance;

            return $transaction;
        });

        $feeGroups = FeeGroup::query()
            ->byPeriod($student->period_id)
            ->select('name', 'uuid', 'id')
            ->get()
            ->transform(function ($feeGroup) use ($fees, $date) {
                $total = $fees->where('installment.fee_group_id', $feeGroup->id)->sum(function ($fee) use ($date) {
                    return $fee->getTotal($date)->value;
                });

                $lateFee = $fees->where('installment.fee_group_id', $feeGroup->id)->sum(function ($fee) use ($date) {
                    return $fee->calculateLateFeeAmount($date)->value;
                });

                $paid = $fees->where('installment.fee_group_id', $feeGroup->id)->sum(function ($fee) {
                    return $fee->getPaid()->value;
                });

                $concession = $fees->where('installment.fee_group_id', $feeGroup->id)->sum(function ($fee) {
                    return $fee->records->sum('concession.value');
                });

                $balance = $total - $paid;

                return [
                    'name' => $feeGroup->name,
                    'uuid' => $feeGroup->uuid,
                    'late_fee' => \Price::from($lateFee),
                    'total' => \Price::from($total),
                    'paid' => \Price::from($paid),
                    'concession' => \Price::from($concession),
                    'balance' => \Price::from($balance),
                    'status' => PaymentStatus::getDetail(PaymentStatus::status($total, $paid)),
                ];
            })
            ->filter(function ($feeGroup) {
                return $feeGroup['total']->value > 0 || $feeGroup['concession']->value > 0;
            })
            ->all();

        $grandTotal = collect($feeGroups)->sum('total.value');
        $grandTotalPaid = collect($feeGroups)->sum('paid.value');
        $grandTotalBalance = collect($feeGroups)->sum('balance.value');

        if (auth()->user()) {
            $transactionResource = TransactionListResource::collection($transactions);
        } else {
            $transactionResource = TransactionListForGuestResource::collection($transactions);
        }

        $registration = null;
        if ($student->start_date->value == $student->joining_date) {
            $registration = Registration::query()
                ->select('uuid', 'code_number', 'fee', 'date')
                ->find($student->admission->registration_id);
        }

        return [
            'student' => [
                'name' => $student->name,
                'uuid' => $student->uuid,
                'batch_name' => $student->batch_name,
                'course_name' => $student->course_name,
                'code_number' => $student->code_number,
                'registration' => $registration ? [
                    'uuid' => $registration->uuid,
                    'code_number' => $registration->code_number,
                    'date' => $registration->date,
                    'fee' => $registration->fee,
                ] : [],
            ],
            'feeGroups' => array_values($feeGroups),
            'fees' => FeeListResource::collection($fees),
            'summary' => [
                'grandTotal' => \Price::from($grandTotal),
                'grandTotalPaid' => \Price::from($grandTotalPaid),
                'grandTotalBalance' => \Price::from($grandTotalBalance),
            ],
            'transactions' => $transactionResource,
            'bankTransfers' => BankTransferResource::collection($bankTransfers),
            'date' => \Cal::date($date),
            'enable_multi_installment_payment' => false,
        ];
    }

    private function headWiseFee(Request $request, Student $student): array
    {
        $date = $request->date ?? today()->toDateString();

        $fees = Fee::query()
            ->whereStudentId($student->id)
            ->get();

        $feeRecords = FeeRecord::query()
            ->with('head')
            ->whereIn('student_fee_id', $fees->pluck('id')->all())
            ->get();

        $feeHeads = (new GetHeadWiseFee)->execute(
            student: $student,
            fees: $fees,
            feeRecords: $feeRecords,
            date: $date,
        );

        $transactionRecords = TransactionRecord::query()
            ->select('transaction_records.*', 'student_fees.uuid as student_fee_uuid')
            ->join('student_fees', 'transaction_records.model_id', '=', 'student_fees.id')
            ->join('transactions', 'transaction_records.transaction_id', '=', 'transactions.id')
            ->whereNull('transactions.cancelled_at')
            ->whereNull('transactions.rejected_at')
            ->where('transactions.head', '=', 'student_fee')
            ->where('transactions.transactionable_type', '=', 'Student')
            ->where('transactions.transactionable_id', '=', $student->id)
            ->get();

        $charges = [];
        $discounts = [];
        foreach ($transactionRecords as $transactionRecord) {
            $additionalCharges = $transactionRecord->getMeta('additional_charges') ?? [];
            $additionalDiscounts = $transactionRecord->getMeta('additional_discounts') ?? [];

            foreach ($additionalCharges as $additionalCharge) {
                $charges[] = $additionalCharge;
            }

            foreach ($additionalDiscounts as $additionalDiscount) {
                $discounts[] = $additionalDiscount;
            }
        }

        $charges = collect($charges)->groupBy('label')->map(function ($items) {
            return $items->sum('amount');
        })->toArray();

        foreach ($charges as $name => $amount) {
            $feeHeads[] = [
                'name' => $name,
                'uuid' => (string) Str::uuid(),
                'amount' => \Price::from($amount),
                'concession' => \Price::from(0),
                'total' => \Price::from($amount),
                'paid' => \Price::from($amount),
                'balance' => \Price::from(0),
            ];
        }

        $discounts = collect($discounts)->groupBy('label')->map(function ($items) {
            return $items->sum('amount');
        });

        foreach ($discounts as $name => $amount) {
            $feeHeads[] = [
                'name' => $name,
                'is_deduction' => true,
                'uuid' => (string) Str::uuid(),
                'amount' => \Price::from($amount),
                'concession' => \Price::from(0),
                'total' => \Price::from($amount),
                'paid' => \Price::from($amount),
                'balance' => \Price::from(0),
            ];
        }

        $additionFeeHeads = collect($feeHeads)->filter(function ($feeHead) {
            return ! Arr::get($feeHead, 'is_deduction');
        });

        $deductionFeeHeads = collect($feeHeads)->filter(function ($feeHead) {
            return Arr::get($feeHead, 'is_deduction');
        });

        $grandTotalAmount = $additionFeeHeads->sum('amount.value') - $deductionFeeHeads->sum('amount.value');
        $grandTotalConcession = $additionFeeHeads->sum('concession.value') - $deductionFeeHeads->sum('concession.value');
        $grandTotal = $additionFeeHeads->sum('total.value') - $deductionFeeHeads->sum('total.value');
        $grandTotalPaid = $additionFeeHeads->sum('paid.value') - $deductionFeeHeads->sum('paid.value');
        $grandTotalBalance = $additionFeeHeads->sum('balance.value') - $deductionFeeHeads->sum('balance.value');

        return [
            'feeHeads' => $feeHeads,
            'fees' => $fees->pluck('uuid')->all(),
            'date' => \Cal::date($date),
            'summary' => [
                'grandTotalAmount' => \Price::from($grandTotalAmount),
                'grandTotalConcession' => \Price::from($grandTotalConcession),
                'grandTotal' => \Price::from($grandTotal),
                'grandTotalPaid' => \Price::from($grandTotalPaid),
                'grandTotalBalance' => \Price::from($grandTotalBalance),
            ],
        ];
    }
}
