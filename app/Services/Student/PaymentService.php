<?php

namespace App\Services\Student;

use App\Actions\Finance\CancelTransaction;
use App\Actions\Finance\CreateTransaction;
use App\Actions\Finance\GetPaymentGateway;
use App\Actions\Student\CreateCustomFeeHead;
use App\Actions\Student\GetPayableInstallment;
use App\Actions\Student\GetStudentFees;
use App\Actions\Student\PayFeeInstallment;
use App\Enums\Finance\DefaultFeeHead;
use App\Enums\Finance\TransactionType;
use App\Helpers\CurrencyConverter;
use App\Http\Resources\Finance\FeeGroupResource;
use App\Http\Resources\Finance\FeeHeadResource;
use App\Http\Resources\Finance\LedgerResource;
use App\Http\Resources\Finance\PaymentMethodResource;
use App\Jobs\SendPushNotification;
use App\Models\Finance\BankTransfer;
use App\Models\Finance\FeeGroup;
use App\Models\Finance\FeeHead;
use App\Models\Finance\Ledger;
use App\Models\Finance\PaymentMethod;
use App\Models\Finance\Transaction;
use App\Models\Finance\TransactionPayment;
use App\Models\Student\Fee;
use App\Models\Student\FeePayment;
use App\Models\Student\FeeRecord;
use App\Models\Student\Student;
use App\Models\TempStorage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function preRequisite(Request $request, Student $student): array
    {
        if ($request->query('type') == 'custom_fee') {
            $customFeeHeads = FeeHeadResource::collection(FeeHead::query()
                ->byPeriod()
                ->whereHas('group', function ($q) {
                    $q->where('meta->is_custom', true);
                })
                ->get());

            return compact('customFeeHeads');
        }

        $paymentGateways = (new GetPaymentGateway)->execute();

        if (auth()->user()->is_student_or_guardian) {
            return compact('paymentGateways');
        }

        $feeGroups = FeeGroupResource::collection(FeeGroup::query()
            ->with('heads')
            ->byPeriod($student->period_id)
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

        return compact('paymentMethods', 'ledgers', 'feeGroups', 'paymentGateways');
    }

    public function getPayment(Request $request, Student $student, string $uuid): Transaction
    {
        $transaction = Transaction::query()
            ->whereTransactionableType('Student')
            ->whereTransactionableId($student->id)
            ->whereHead('student_fee')
            ->whereUuid($uuid)
            ->firstOrFail();

        return $transaction;
    }

    public function getPaymentByReferenceNumber(Request $request, Student $student): Transaction
    {
        $transaction = Transaction::query()
            ->whereTransactionableType('Student')
            ->whereTransactionableId($student->id)
            ->whereHead('student_fee')
            ->whereIsOnline(true)
            ->whereNotNull('processed_at')
            ->where('payment_gateway->reference_number', '=', $request->query('reference_number'))
            ->firstOrFail();

        return $transaction;
    }

    public function getInstallmentDetails(Transaction $transaction)
    {
        //
    }

    public function storeTempPayment(Request $request, Student $student): array
    {
        $tempStorage = TempStorage::forceCreate([
            'user_id' => auth()->user()->id,
            'type' => 'student_fee_payment',
            'values' => [
                'student' => $student->uuid,
                'amount' => $request->amount,
                'date' => $request->date ?: today()->toDateString(),
                'fee_group' => $request->fee_group,
                'fee_head' => $request->fee_head,
                'fee_installment' => $request->fee_installment,
            ],
        ]);

        return [
            'url' => url('app/payments/'.$tempStorage->uuid),
        ];
    }

    public function makePayment(Request $request, Student $student): void
    {
        (new GetStudentFees)->validatePreviousDue($student);

        $studentFees = (new GetPayableInstallment)->execute($request, $student);

        $request->merge([
            'period_id' => $student->period_id,
            'transactionable_type' => 'Student',
            'transactionable_id' => $student->id,
            'head' => 'student_fee',
            'type' => TransactionType::RECEIPT->value,
        ]);

        $params = $request->all();
        $params['batch_id'] = $student->batch_id;
        $params['payments'] = [
            [
                'ledger_id' => $request->ledger?->id,
                'amount' => $request->amount,
                'payment_method_id' => $request->payment_method_id,
                'payment_method_details' => $request->payment_method_details,
            ],
        ];

        $totalAdditionalCharge = array_sum(array_column($request->additional_charges ?? [], 'amount'));
        $totalAdditionalDiscount = array_sum(array_column($request->additional_discounts ?? [], 'amount'));

        \DB::beginTransaction();

        $transaction = (new CreateTransaction)->execute($params);

        // $payableAmount = $transaction->amount->value;
        $payableAmount = $transaction->amount->value + $totalAdditionalDiscount - $totalAdditionalCharge;

        foreach ($studentFees as $index => $studentFee) {

            $params = [];
            if ($index == 0 && ($totalAdditionalCharge > 0 || $totalAdditionalDiscount > 0)) {
                if ($totalAdditionalCharge) {
                    $params['additional_charges'] = $request->additional_charges ?? [];
                }
                if ($totalAdditionalDiscount) {
                    $params['additional_discounts'] = $request->additional_discounts ?? [];
                }
            }

            $payableAmount = (new PayFeeInstallment)->execute($studentFee, $transaction, $payableAmount, $params);
        }

        \DB::commit();

        $this->sendPaymentNotification($student, $transaction, $studentFee);
    }

    private function sendPaymentNotification(Student $student, Transaction $transaction, Fee $studentFee, array $params = [])
    {
        if (! config('config.notification.enable_mobile_push_notification')) {
            return;
        }

        $users = User::query()
            ->whereIn('id', [$student->user_id])
            ->get();

        SendPushNotification::dispatch($users, 'fee-payment', [
            'variables' => [
                'fee_group' => $studentFee->installment?->group?->name,
                'voucher_number' => $transaction->code_number,
                'amount' => $transaction->amount->formatted,
                'date' => $transaction->date->formatted,
            ],
            'data' => [
                'type' => 'fee-payment',
            ],
        ]);
    }

    public function updatePayment(Request $request, Student $student, string $uuid): void
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'code_number' => 'required|max:50',
            'remarks' => 'nullable|max:255',
            'ledger' => 'required|uuid',
            'payment_method' => 'required|uuid',
            'instrument_number' => 'nullable|max:20',
            'instrument_date' => 'nullable|date_format:Y-m-d',
            'clearing_date' => 'nullable|date_format:Y-m-d',
            'bank_detail' => 'nullable|min:2|max:100',
            'reference_number' => 'nullable|max:20',
        ]);

        $transaction = Transaction::query()
            ->whereUuid($uuid)
            ->whereHead('student_fee')
            ->where('transactionable_type', 'Student')
            ->where('transactionable_id', $student->id)
            ->getOrFail(trans('student.payment.payment'));

        if (! $transaction->isFeeReceiptEditable()) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }

        \DB::beginTransaction();

        $this->updatePayments($request, $transaction);

        if ($transaction->code_number != $request->code_number) {
            $existingCodeNumber = Transaction::query()
                ->join('periods', 'periods.id', '=', 'transactions.period_id')
                ->where('periods.team_id', auth()->user()->current_team_id)
                ->where('transactions.uuid', '!=', $uuid)
                ->where('transactions.code_number', $request->code_number)
                ->exists();

            if ($existingCodeNumber) {
                throw ValidationException::withMessages(['code_number' => trans('global.duplicate', ['attribute' => trans('finance.transaction.props.code_number')])]);
            }

            $transaction->number_format = null;
            $transaction->number = null;
            $transaction->code_number = $request->code_number;
        }

        $transaction->date = $request->date;
        $transaction->remarks = $request->remarks;
        $transaction->save();

        \DB::commit();
    }

    private function updatePayments(Request $request, Transaction $transaction)
    {
        $paymentMethod = PaymentMethod::query()
            ->byTeam()
            ->where('is_payment_gateway', false)
            ->whereUuid($request->payment_method)
            ->getOrFail(trans('finance.payment_method.payment_method'), 'payment_method');

        $ledger = Ledger::query()
            ->byTeam()
            ->subType('primary')
            ->whereUuid($request->ledger)
            ->getOrFail(trans('finance.ledger.ledger'), 'ledger');

        $newPayments = [
            [
                'ledger_id' => $ledger?->id,
                'amount' => $transaction->amount->value,
                'payment_method_id' => $paymentMethod?->id,
                'payment_method_details' => [
                    'instrument_number' => $request->instrument_number,
                    'instrument_date' => $request->instrument_date,
                    'clearing_date' => $request->clearing_date,
                    'bank_detail' => $request->bank_detail,
                    'reference_number' => $request->reference_number,
                ],
            ],
        ];

        $payments = $transaction->payments;

        // temporary fix if ledger_id is null, should be removed after some time
        if ($payments->where('ledger_id', null)->count()) {
            $transactionPayments = TransactionPayment::query()
                ->where('transaction_id', $transaction->id)
                ->whereNull('ledger_id')
                ->get();

            foreach ($transactionPayments as $transactionPayment) {
                $transactionPayment->setMeta(['ledger_updated_from_null' => true]);
                $transactionPayment->ledger_id = $ledger->id;
                $transactionPayment->save();
            }

            $transaction->refresh();

            $payments = $transaction->payments;
        }

        foreach ($newPayments as $payment) {
            $ledgerId = Arr::get($payment, 'ledger_id');

            $existingPayment = $payments->firstWhere('ledger_id', $ledgerId);

            if ($existingPayment) {
                if ($existingPayment->amount->value != Arr::get($payment, 'amount', 0)) {
                    $existingPayment->ledger->reversePrimaryBalance($transaction->type, $existingPayment->amount->value);
                    $existingPayment->amount = Arr::get($payment, 'amount', 0);
                }

                $existingPayment->payment_method_id = Arr::get($payment, 'payment_method_id');
                $existingPayment->details = Arr::get($payment, 'payment_method_details', []);
                $existingPayment->save();
            } else {
                TransactionPayment::forceCreate([
                    'transaction_id' => $transaction->id,
                    'ledger_id' => $ledgerId,
                    'payment_method_id' => Arr::get($payment, 'payment_method_id'),
                    'details' => Arr::get($payment, 'payment_method_details', []),
                    'amount' => Arr::get($payment, 'amount', 0),
                    'description' => Arr::get($payment, 'description'),
                ]);

                $ledger = Ledger::find($ledgerId);
                $ledger->updatePrimaryBalance($transaction->type, Arr::get($payment, 'amount', 0));
            }
        }

        $unnecessaryPayments = TransactionPayment::query()
            ->where('transaction_id', $transaction->id)
            ->whereNotIn('ledger_id', Arr::pluck($newPayments, 'ledger_id'))
            ->get();

        foreach ($unnecessaryPayments as $unnecessaryPayment) {
            $unnecessaryPayment->ledger->reversePrimaryBalance($transaction->type, $unnecessaryPayment->amount->value);
            $unnecessaryPayment->delete();
        }
    }

    public function cancelPayment(Request $request, Student $student, string $uuid): void
    {
        $transaction = Transaction::query()
            ->whereUuid($uuid)
            ->whereHead('student_fee')
            ->where('transactionable_type', 'Student')
            ->where('transactionable_id', $student->id)
            ->getOrFail(trans('student.payment.payment'));

        if (! $transaction->isFeeReceiptEditable()) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }

        $customFeeHead = null;
        if ($request->boolean('is_rejected')) {
            $request->validate([
                'rejected_date' => 'required|date_format:Y-m-d',
                'rejection_charge' => 'required|numeric|min:0',
                'custom_fee_head' => 'nullable|uuid',
                'rejection_remarks' => 'required|min:3|max:255',
            ]);

            if ($request->rejected_date < $transaction->date->value) {
                throw ValidationException::withMessages(['rejected_date' => trans('validation.after_or_equal', ['attribute' => trans('finance.transaction.props.rejected_date'), 'date' => $transaction->date->formatted])]);
            }

            if ($request->rejection_charge) {
                $customFeeHead = FeeHead::query()
                    ->byPeriod()
                    ->whereHas('group', function ($q) {
                        $q->where('meta->is_custom', true);
                    })
                    ->whereUuid($request->custom_fee_head)
                    ->getOrFail(trans('student.fee.custom_fee'), 'custom_fee_head');
            }
        } else {
            $request->validate([
                'cancellation_remarks' => 'required|min:3|max:255',
            ]);
        }

        if ($request->is_rejected && $request->rejection_charge > 0 && empty($customFeeHead)) {
            throw ValidationException::withMessages(['custom_fee_head' => trans('validation.required', ['attribute' => trans('student.fee.custom_fee')])]);
        }

        \DB::beginTransaction();

        (new CancelTransaction)->execute($request, $transaction);

        if ($request->is_rejected && $request->rejection_charge > 0) {
            $feeRecord = (new CreateCustomFeeHead)->execute($student, [
                'fee_head_id' => $customFeeHead->id,
                'amount' => $request->rejection_charge,
                'due_date' => $request->rejected_date,
                'remarks' => $request->rejection_remarks,
                'meta' => [
                    'is_force_set' => true,
                    'transaction_id' => $transaction->id,
                ],
            ]);
        }

        foreach ($transaction->records as $transactionRecord) {
            if ($transactionRecord->model_type == 'StudentFee') {

                $additionalCharge = collect($transactionRecord->getMeta('additional_charges', []))->sum('amount');
                $additionalDiscount = collect($transactionRecord->getMeta('additional_discounts', []))->sum('amount');

                $additionalAmount = $additionalCharge - $additionalDiscount;
                $paidAmount = $transactionRecord->amount->value;

                Fee::query()
                    ->whereId($transactionRecord->model_id)
                    ->update([
                        'total' => \DB::raw('total - '.$additionalAmount),
                        'paid' => \DB::raw('paid - '.$paidAmount),
                        'additional_charge' => \DB::raw('additional_charge - '.$additionalCharge),
                        'additional_discount' => \DB::raw('additional_discount - '.$additionalDiscount),
                    ]);
            }
        }

        $bankTransferId = $transaction->getMeta('bank_transfer_id');

        if ($bankTransferId) {
            $bankTransfer = BankTransfer::query()
                ->find($bankTransferId);

            if ($bankTransfer) {
                $bankTransfer->update([
                    'status' => 'rejected',
                    'approver_id' => auth()->user()->id,
                    'comment' => $request->cancellation_remarks ?? $request->rejection_remarks,
                    'processed_at' => now()->toDateTimeString(),
                ]);
            }
        }

        $feePayments = FeePayment::query()
            ->whereTransactionId($transaction->id)
            ->get();

        foreach ($feePayments as $feePayment) {
            $balanceAmount = $feePayment->amount->value;

            $studentFeeRecords = FeeRecord::query()
                ->whereStudentFeeId($feePayment->student_fee_id)
                ->whereFeeHeadId($feePayment->fee_head_id)
                ->where('default_fee_head', $feePayment->default_fee_head)
                ->where('paid', '>', 0)
                ->get();

            foreach ($studentFeeRecords as $studentFeeRecord) {
                if ($studentFeeRecord && $studentFeeRecord->default_fee_head == DefaultFeeHead::LATE_FEE) {
                    Fee::query()
                        ->whereId($studentFeeRecord->student_fee_id)
                        ->update([
                            'total' => \DB::raw('total - '.$balanceAmount),
                        ]);
                }

                if ($studentFeeRecord->paid->value > $balanceAmount) {
                    $studentFeeRecord->paid = $studentFeeRecord->paid->value - $balanceAmount;

                    if ($studentFeeRecord->default_fee_head == DefaultFeeHead::LATE_FEE) {
                        $studentFeeRecord->amount = $studentFeeRecord->amount->value - $balanceAmount;
                    }

                    $studentFeeRecord->save();

                    $balanceAmount = 0;
                } else {
                    $balanceAmount -= $studentFeeRecord->paid->value;

                    $studentFeeRecord->paid = 0;
                    $studentFeeRecord->save();
                }

                if ($studentFeeRecord && $studentFeeRecord->default_fee_head == DefaultFeeHead::LATE_FEE) {
                    if ($studentFeeRecord->paid->value == 0) {
                        $studentFeeRecord->delete();
                    }
                }

                if ($balanceAmount <= 0) {
                    break;
                }
            }
        }

        \DB::commit();
    }

    public function getPaymentRows(Transaction $transaction)
    {
        $rows = [];

        $totalDue = 0;
        $concession = 0;
        $totalPaid = 0;
        $totalBalance = 0;

        foreach ($transaction->records as $transactionRecord) {
            $row = [];

            $row[] = [
                'key' => 'heading',
                'type' => 'installment_title',
                'label' => $transactionRecord->model->installment->title.' '.trans('finance.fee_structure.props.due_date').': '.$transactionRecord->model->installment->due_date->formatted,
            ];

            $row[] = [
                'key' => 'heading',
                'type' => 'due',
                'label' => trans('finance.fee.due'),
            ];

            $row[] = [
                'key' => 'heading',
                'type' => 'concession',
                'label' => trans('finance.fee.concession'),
            ];

            $row[] = [
                'key' => 'heading',
                'type' => 'amount',
                'label' => trans('finance.fee.paid'),
            ];

            $rows[] = $row;

            foreach ($transactionRecord->model->payments->filter(function ($payment) use ($transaction) {
                return $payment->transaction_id == $transaction->id && ! in_array($payment->default_fee_head?->value, ['additional_discount', 'additional_charge']);
            }) as $feePayment) {
                $row = [];

                $feeRecord = FeeRecord::query()
                    ->whereStudentFeeId($feePayment->student_fee_id)
                    ->where(function ($q) use ($feePayment) {
                        $q->where(function ($q) use ($feePayment) {
                            $q->whereNotNull('fee_head_id')->where('fee_head_id', $feePayment->fee_head_id);
                        })->orWhere(function ($q) use ($feePayment) {
                            $q->whereNotNull('default_fee_head')->where('default_fee_head', $feePayment->default_fee_head);
                        });
                    })
                    ->first();

                $totalBalance += ($feeRecord?->getBalance()?->value ?? 0);

                if ($feePayment->fee_head_id) {
                    $row[] = [
                        'key' => 'record',
                        'type' => 'fee_title',
                        'label' => $feePayment->head->name,
                    ];
                } else {
                    $row[] = [
                        'key' => 'record',
                        'type' => 'fee_title',
                        'label' => $feePayment->getDefaultFeeHeadName(),
                    ];
                }

                $amount = $feeRecord?->amount->value ?? 0;
                $feeRecordConcession = $feeRecord?->concession->value ?? 0;
                $due = $amount - $feeRecordConcession;

                $totalDue += $due;

                if ($feeRecordConcession > 0) {
                    $row[] = [
                        'key' => 'record',
                        'type' => 'due',
                        'label' => $feeRecord?->amount?->formatted ?? '-',
                        'with_concession' => \Price::from($due)->formatted ?? '',
                    ];
                } else {
                    $row[] = [
                        'key' => 'record',
                        'type' => 'due',
                        'label' => $feeRecord?->amount?->formatted ?? '-',
                    ];
                }

                $row[] = [
                    'key' => 'record',
                    'type' => 'concession',
                    'label' => $feePayment->concession_amount->formatted,
                ];

                $row[] = [
                    'key' => 'record',
                    'type' => 'amount',
                    'label' => $feePayment->amount->formatted,
                ];

                $concession += $feePayment->concession_amount->value;
                $totalPaid += $feePayment->amount->value;

                $rows[] = $row;
            }
        }

        foreach ($transactionRecord->getAdditionalFees() ?? [] as $fee) {
            $row = [];

            $row[] = [
                'key' => 'record',
                'type' => 'fee_title',
                'label' => $fee['label'],
            ];

            $row[] = [
                'key' => 'record',
                'type' => 'due',
                'label' => '',
            ];

            $row[] = [
                'key' => 'record',
                'type' => 'concession',
                'label' => '',
            ];

            $row[] = [
                'key' => 'record',
                'type' => 'amount',
                'label' => $fee['amount']->formatted,
            ];

            $rows[] = $row;
        }

        foreach ($transactionRecord->getAdditionalFees('discounts') ?? [] as $fee) {
            $row = [];

            $row[] = [
                'key' => 'record',
                'type' => 'fee_title',
                'label' => $fee['label'],
            ];

            $row[] = [
                'key' => 'record',
                'type' => 'due',
                'label' => '',
            ];

            $row[] = [
                'key' => 'record',
                'type' => 'concession',
                'label' => '',
            ];

            $row[] = [
                'key' => 'record',
                'type' => 'amount',
                'label' => '(-) '.$fee['amount']->formatted,
            ];

            $rows[] = $row;
        }

        $row = [];

        $row[] = [
            'key' => 'footer',
            'type' => 'total',
            'label' => trans('finance.fee.total'),
        ];

        $row[] = [
            'key' => 'footer',
            'type' => 'due',
            'label' => \Price::from($totalDue)->formatted,
        ];

        $row[] = [
            'key' => 'footer',
            'type' => 'concession',
            'label' => \Price::from($concession)->formatted,
        ];

        $row[] = [
            'key' => 'footer',
            'type' => 'amount',
            'label' => $transaction->amount->formatted,
        ];

        $rows[] = $row;

        $row = [];

        $row[] = [
            'key' => 'footer',
            'colspan' => 100,
            'type' => 'amount',
            'align' => 'right',
            'label' => CurrencyConverter::toWord($transaction->amount->value),
        ];

        $rows[] = $row;

        $row = [];

        $row[] = [
            'key' => 'footer',
            'type' => 'balance',
            'label' => trans('finance.fee.balance'),
        ];

        $row[] = [
            'key' => 'footer',
            'type' => 'due',
            'label' => '',
        ];

        $row[] = [
            'key' => 'footer',
            'type' => 'concession',
            'label' => '',
        ];

        $row[] = [
            'key' => 'footer',
            'type' => 'amount',
            'label' => \Price::from($totalBalance)->formatted,
        ];

        if (false) {
            $rows[] = $row;
        }

        $rows = collect($rows)->map(function ($row) {
            return collect($row)->filter(function ($cell) {
                return $cell['type'] != 'due';
            })->values()->all();
        })->values()->all();

        if ($concession == 0) {
            $rows = collect($rows)->map(function ($row) {
                return collect($row)->filter(function ($cell) {
                    return $cell['type'] != 'concession';
                })->values()->all();
            })->values()->all();
        }

        return $rows;
    }
}
