<?php

namespace App\Imports\Student;

use App\Actions\Finance\CreateTransaction;
use App\Actions\Student\PayFeeInstallment;
use App\Concerns\ItemImport;
use App\Enums\Finance\TransactionType;
use App\Helpers\CalHelper;
use App\Models\Finance\FeeGroup;
use App\Models\Finance\FeeStructure;
use App\Models\Finance\Ledger;
use App\Models\Finance\PaymentMethod;
use App\Models\Finance\Transaction;
use App\Models\Student\Student;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class FeePaymentImport implements ToCollection, WithHeadingRow
{
    use ItemImport;

    protected $limit = 1000;

    public function collection(Collection $rows)
    {
        $this->validateHeadings();

        if (count($rows) > $this->limit) {
            throw ValidationException::withMessages(['message' => trans('general.errors.max_import_limit_crossed', ['attribute' => $this->limit])]);
        }

        $logFile = $this->getLogFile('student_fee_payment');

        $errors = $this->validate($rows);

        if (empty($errors)) {
            $errors = $this->secondaryValidate($rows);
        }

        $this->checkForErrors('student_fee_payment', $errors);

        if (! request()->boolean('validate') && ! \Storage::disk('local')->exists($logFile)) {
            $this->import($rows);
        }
    }

    private function import(Collection $rows)
    {
        activity()->disableLogging();

        $students = Student::query()
            ->join('admissions', 'admissions.id', '=', 'students.admission_id')
            ->byPeriod()
            ->select('students.*', 'admissions.code_number', 'admissions.leaving_date')
            ->get();

        $feeGroups = FeeGroup::query()
            ->byPeriod()
            ->get();

        $feeStructures = FeeStructure::query()
            ->with('installments:id,fee_structure_id,fee_group_id,title')
            ->byPeriod()
            ->get();

        $ledgers = Ledger::query()
            ->byTeam()
            ->subType('primary')
            ->get();

        $paymentMethods = PaymentMethod::query()
            ->byTeam()
            ->where('is_payment_gateway', false)
            ->get();

        foreach ($rows as $row) {
            $student = $students->firstWhere('code_number', Arr::get($row, 'admission_number'));

            if (is_int(Arr::get($row, 'date'))) {
                $date = Date::excelToDateTimeObject(Arr::get($row, 'date'))->format('Y-m-d');
            } else {
                $date = Carbon::parse(Arr::get($row, 'date'))->toDateString();
            }

            if (is_int(Arr::get($row, 'clearing_date'))) {
                $clearingDate = Date::excelToDateTimeObject(Arr::get($row, 'clearing_date'))->format('Y-m-d');
            } else {
                $clearingDate = Carbon::parse(Arr::get($row, 'clearing_date'))->toDateString();
            }

            if (is_int(Arr::get($row, 'instrument_date'))) {
                $instrumentDate = Date::excelToDateTimeObject(Arr::get($row, 'instrument_date'))->format('Y-m-d');
            } else {
                $instrumentDate = Carbon::parse(Arr::get($row, 'instrument_date'))->toDateString();
            }

            $feeGroup = $feeGroups->where('name', Arr::get($row, 'fee_group'))->first();

            $feeStructure = $feeStructures->where('id', $student->fee_structure_id)->first();

            $feeInstallment = $feeStructure->installments->where('fee_group_id', $feeGroup->id)->first();

            $studentFees = $student->fees->where('fee_installment_id', $feeInstallment->id);

            $ledger = $ledgers->firstWhere('name', Arr::get($row, 'account'));
            $paymentMethod = $paymentMethods->firstWhere('name', Arr::get($row, 'payment_method'));

            $params = [
                'code_number' => Arr::get($row, 'voucher_number'),
                'period_id' => $student->period_id,
                'transactionable_type' => 'Student',
                'transactionable_id' => $student->id,
                'head' => 'student_fee',
                'type' => TransactionType::RECEIPT->value,
                'batch_id' => $student->batch_id,
                'date' => $date,
                'amount' => Arr::get($row, 'amount', 0) + Arr::get($row, 'late_fee', 0),
                'late_fee' => Arr::get($row, 'late_fee', 0),
                'remarks' => Arr::get($row, 'remarks'),
                'instrument_number' => Arr::get($row, 'instrument_number'),
                'instrument_date' => $instrumentDate,
                'clearing_date' => $clearingDate,
                'bank_detail' => Arr::get($row, 'bank_detail'),
                'reference_number' => Arr::get($row, 'reference_number'),
                'meta' => [
                    'payment_method_code' => $paymentMethod?->code,
                    'ledger_code' => $ledger?->code,
                ],
            ];

            $params['payments'] = [
                [
                    'ledger_id' => $ledger?->id,
                    'amount' => Arr::get($row, 'amount'),
                    'payment_method_id' => $paymentMethod?->id,
                ],
            ];

            $totalAdditionalCharge = array_sum(array_column($request->additional_charges ?? [], 'amount'));
            $totalAdditionalDiscount = array_sum(array_column($request->additional_discounts ?? [], 'amount'));

            \DB::beginTransaction();

            $transaction = (new CreateTransaction)->execute($params);

            // $payableAmount = $transaction->amount->value;
            $payableAmount = $transaction->amount->value + $totalAdditionalDiscount - $totalAdditionalCharge;

            foreach ($studentFees as $index => $studentFee) {

                $studentFee->setMeta([
                    'custom_late_fee' => true,
                    'late_fee_amount' => Arr::get($params, 'late_fee', 0),
                    'original_late_fee_amount' => 0,
                ]);

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
        }

        activity()->enableLogging();
    }

    private function validate(Collection $rows)
    {
        $errors = [];

        $students = Student::query()
            ->join('admissions', 'admissions.id', '=', 'students.admission_id')
            ->join('contacts', 'students.contact_id', '=', 'contacts.id')
            ->byPeriod()
            ->select('students.id', 'admissions.code_number', 'admissions.leaving_date', 'fee_structure_id', \DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ") as name'))
            ->get();

        $feeGroups = FeeGroup::query()
            ->byPeriod()
            ->get();

        $feeStructures = FeeStructure::query()
            ->with('installments:id,fee_structure_id,fee_group_id,title')
            ->byPeriod()
            ->get();

        $existingVoucherNumbers = Transaction::query()
            ->byPeriod()
            ->select('code_number')
            ->pluck('code_number')
            ->toArray();

        $ledgers = Ledger::query()
            ->byTeam()
            ->subType('primary')
            ->get()
            ->pluck('name')
            ->toArray();

        $paymentMethods = PaymentMethod::query()
            ->byTeam()
            ->where('is_payment_gateway', false)
            ->get()
            ->pluck('name')
            ->toArray();

        $voucherNumbers = [];
        foreach ($rows as $index => $row) {
            $rowNo = (int) $index + 2;

            $voucherNumber = Arr::get($row, 'voucher_number');
            $name = Arr::get($row, 'name');
            $codeNumber = Arr::get($row, 'admission_number');
            $date = Arr::get($row, 'date');
            $instrumentDate = Arr::get($row, 'instrument_date');
            $clearingDate = Arr::get($row, 'clearing_date');
            $feeGroupName = Arr::get($row, 'fee_group');
            $feeInstallmentName = Arr::get($row, 'fee_installment');
            $ledgerName = Arr::get($row, 'account');
            $paymentMethodName = Arr::get($row, 'payment_method');
            $amount = Arr::get($row, 'amount');
            $lateFee = Arr::get($row, 'late_fee');
            $remarks = Arr::get($row, 'remarks');

            if ($voucherNumber && in_array($voucherNumber, $existingVoucherNumbers)) {
                $errors[] = $this->setError($rowNo, trans('finance.transaction.props.code_number'), 'exists');
            }

            if ($voucherNumber && in_array($voucherNumber, $voucherNumbers)) {
                $errors[] = $this->setError($rowNo, trans('finance.transaction.props.code_number'), 'duplicate');
            }

            if (! $codeNumber) {
                $errors[] = $this->setError($rowNo, trans('student.admission.props.code_number'), 'required');
            } elseif ($codeNumber && ! $students->where('code_number', $codeNumber)->first()) {
                $errors[] = $this->setError($rowNo, trans('student.admission.props.code_number'), 'invalid');
            } elseif ($codeNumber && $students->where('name', $name)->where('code_number', $codeNumber)->count() == 0) {
                $errors[] = $this->setError($rowNo, trans('student.props.name'), 'invalid');
            } else {
                $student = $students->where('code_number', $codeNumber)->first();

                if (! $student) {
                    $errors[] = $this->setError($rowNo, trans('student.admission.props.code_number'), 'invalid');
                } elseif (! $student?->fee_structure_id) {
                    $errors[] = $this->setError($rowNo, trans('student.fee.fee'), 'custom', ['message' => trans('student.fee.set_fee_info')]);
                }
            }

            if (! $date) {
                $errors[] = $this->setError($rowNo, trans('finance.transaction.props.date'), 'required');
            }

            if (is_int($date)) {
                $date = Date::excelToDateTimeObject($date)->format('Y-m-d');
            }

            if ($date && ! CalHelper::validateDate($date)) {
                $errors[] = $this->setError($rowNo, trans('finance.transaction.props.date'), 'invalid');
            }

            if ($instrumentDate && is_int($instrumentDate)) {
                $instrumentDate = Date::excelToDateTimeObject($instrumentDate)->format('Y-m-d');
            }

            if ($instrumentDate && ! CalHelper::validateDate($instrumentDate)) {
                $errors[] = $this->setError($rowNo, trans('finance.transaction.props.instrument_date'), 'invalid');
            }

            if ($clearingDate && is_int($clearingDate)) {
                $clearingDate = Date::excelToDateTimeObject($clearingDate)->format('Y-m-d');
            }

            if ($clearingDate && ! CalHelper::validateDate($clearingDate)) {
                $errors[] = $this->setError($rowNo, trans('finance.transaction.props.clearing_date'), 'invalid');
            }

            if (! $feeInstallmentName) {
                $errors[] = $this->setError($rowNo, trans('finance.fee_structure.installment'), 'required');
            }

            if (! $feeGroupName) {
                $errors[] = $this->setError($rowNo, trans('finance.fee_group.fee_group'), 'required');
            } else {
                $feeGroup = $feeGroups->where('name', $feeGroupName)->first();

                if (! $feeGroup) {
                    $errors[] = $this->setError($rowNo, trans('finance.fee_group.fee_group'), 'invalid');
                } elseif ($student && $student->fee_structure_id) {
                    $feeStructure = $feeStructures->where('id', $student->fee_structure_id)->first();

                    if (! $feeStructure) {
                        $errors[] = $this->setError($rowNo, trans('finance.fee_structure.installment'), 'custom', ['message' => trans('global.could_not_find', ['attribute' => trans('finance.fee_structure.fee_structure')])]);
                    } else {
                        $installment = $feeStructure->installments->where('fee_group_id', $feeGroup->id)->where('title', $feeInstallmentName)->first();

                        if (! $installment) {
                            $errors[] = $this->setError($rowNo, trans('finance.fee_structure.installment'), 'custom', ['message' => trans('global.could_not_find', ['attribute' => trans('finance.fee_structure.installment')])]);
                        }
                    }
                }
            }

            if (empty($amount)) {
                $errors[] = $this->setError($rowNo, trans('student.fee.props.amount'), 'required');
            }

            if ($amount && ! is_numeric($amount)) {
                $errors[] = $this->setError($rowNo, trans('student.fee.props.amount'), 'numeric');
            }

            if ($amount && $amount < 0) {
                $errors[] = $this->setError($rowNo, trans('student.fee.props.amount'), 'min', ['min' => 0]);
            }

            if (! is_numeric($lateFee)) {
                $errors[] = $this->setError($rowNo, trans('finance.fee_structure.props.late_fee'), 'numeric');
            }

            if ($lateFee && $lateFee < 0) {
                $errors[] = $this->setError($rowNo, trans('finance.fee_structure.props.late_fee'), 'min', ['min' => 0]);
            }

            if ($remarks && strlen($remarks) > 500) {
                $errors[] = $this->setError($rowNo, trans('student.fee.props.remarks'), 'max', ['max' => 500]);
            }

            if (! $ledgerName) {
                $errors[] = $this->setError($rowNo, trans('finance.ledger.ledger'), 'invalid');
            } elseif ($ledgerName && ! in_array($ledgerName, $ledgers)) {
                $errors[] = $this->setError($rowNo, trans('finance.ledger.ledger'), 'invalid');
            }

            if (! $paymentMethodName) {
                $errors[] = $this->setError($rowNo, trans('finance.payment_method.payment_method'), 'invalid');
            } elseif ($paymentMethodName && ! in_array($paymentMethodName, $paymentMethods)) {
                $errors[] = $this->setError($rowNo, trans('finance.payment_method.payment_method'), 'invalid');
            }

            $voucherNumbers[] = $voucherNumber;
        }

        return $errors;
    }

    private function secondaryValidate(Collection $rows)
    {
        $errors = [];

        $students = Student::query()
            ->join('admissions', 'admissions.id', '=', 'students.admission_id')
            ->select('students.id', 'fee_structure_id', 'admissions.code_number')
            ->with('fees')
            ->get();

        $feeGroups = FeeGroup::query()
            ->byPeriod()
            ->get();

        $feeStructures = FeeStructure::query()
            ->with('installments:id,fee_structure_id,fee_group_id,title')
            ->byPeriod()
            ->get();

        $groupedRows = $rows
            ->groupBy(function ($item) {
                return $item['admission_number'].'|'.$item['fee_group'].'|'.$item['fee_installment'];
            });

        foreach ($groupedRows as $group) {
            foreach ($group as $index => $row) {
                if (Arr::get($row, 'late_fee') > 0 && $index != count($group) - 1) {
                    $errors[] = $this->setError(Arr::get($row, 'admission_number').' - '.trans('student.fee.multi_installment'), trans('finance.fee_structure.late_fee'), 'custom', ['message' => trans('student.fee.can_apply_late_fee_in_last_installment')]);
                }
            }
        }

        $groupedRows = $rows
            ->groupBy(function ($item) {
                return $item['admission_number'].'|'.$item['fee_group'].'|'.$item['fee_installment'];
            })
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'admission_number' => $first['admission_number'],
                    'fee_group' => $first['fee_group'],
                    'fee_installment' => $first['fee_installment'],
                    'total_amount' => $group->sum('amount'),
                    'total_late_fee' => $group->sum('late_fee'),
                ];
            })
            ->values();

        foreach ($groupedRows as $index => $row) {
            $rowNo = (int) $index + 2;

            $student = $students->firstWhere('code_number', Arr::get($row, 'admission_number'));

            $feeGroup = $feeGroups->where('name', Arr::get($row, 'fee_group'))->first();

            $feeStructure = $feeStructures->where('id', $student->fee_structure_id)->first();

            $feeInstallment = $feeStructure->installments->where('fee_group_id', $feeGroup->id)->first();

            $fee = $student->fees->where('fee_installment_id', $feeInstallment->id)->first();

            if (! $fee) {
                $errors[] = $this->setError($rowNo, trans('finance.fee_structure.installment'), 'custom', ['message' => trans('global.could_not_find', ['attribute' => trans('finance.fee_structure.fee_structure')])]);
            }

            if ($fee) {
                $balance = $fee->total->value - $fee->paid->value;

                if (Arr::get($row, 'total_amount', 0) > $balance) {
                    $errors[] = $this->setError(Arr::get($row, 'admission_number').' - '.trans('student.fee.multi_installment'), trans('student.fee.props.amount'), 'custom', ['message' => trans('student.fee.could_not_make_excess_payment', ['attribute' => \Price::from($balance)->formatted])]);
                }

                if (Arr::get($row, 'total_late_fee', 0) > 0 && Arr::get($row, 'total_amount') != $balance) {
                    $errors[] = $this->setError(Arr::get($row, 'admission_number').' - '.trans('student.fee.multi_installment'), trans('finance.fee_structure.late_fee'), 'custom', ['message' => trans('student.fee.could_not_pay_late_fee_if_balance_mismatch', ['attribute' => \Price::from($balance)->formatted])]);
                }
            }
        }

        return $errors;
    }
}
