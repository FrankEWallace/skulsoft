<?php

namespace App\Services\Finance\Report;

use App\Contracts\ListGenerator;
use App\Exports\Finance\Report\DetailedFeePaymentExport;
use App\Models\Academic\Period;
use App\Models\Finance\FeeHead;
use App\Models\Finance\PaymentMethod;
use App\Models\Finance\Transaction;
use App\Models\Student\FeePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class DetailedFeePaymentListService extends ListGenerator
{
    public function list(Request $request)
    {
        $startDate = $request->query('start_date', today()->subWeek()->toDateString());
        $endDate = $request->query('end_date', today()->toDateString());

        $periods = Period::query()
            ->byTeam()
            ->get();

        $paymentMethods = PaymentMethod::query()
            ->byTeam()
            ->get();

        $feeHeads = FeeHead::query()
            ->select('id', 'name')
            ->whereIn('period_id', $periods->pluck('id'))
            ->orWhereHas('group', function ($query) use ($periods) {
                $query->whereIn('period_id', $periods->pluck('id'));
            })
            ->get();

        $uniqueFeeHeads = array_unique($feeHeads->pluck('name')->toArray());

        array_unshift($uniqueFeeHeads, trans('student.registration.fee'));
        array_push($uniqueFeeHeads, trans('finance.fee.default_fee_heads.transport_fee'));
        array_push($uniqueFeeHeads, trans('finance.fee.default_fee_heads.additional_charge'));
        array_push($uniqueFeeHeads, trans('finance.fee.default_fee_heads.additional_discount'));
        array_push($uniqueFeeHeads, trans('finance.fee.default_fee_heads.late_fee'));

        $transactions = Transaction::query()
            ->select('transactions.id', 'transactions.uuid', 'transactions.number as serial_number', 'transactions.code_number as voucher_number', 'transactions.date', 'transactions.transactionable_type', 'transactions.type', 'transactions.amount', 'transactions.transactionable_id as student_id', 'transactions.is_online', 'transactions.payment_gateway', 'transactions.cancelled_at', 'transactions.rejected_at', 'transactions.processed_at', 'students.uuid as student_uuid', 'students.roll_number', 'students.batch_id', 'students.contact_id', \DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ") as name'), 'admissions.code_number as admission_code_number', 'admissions.joining_date', 'admissions.leaving_date', 'batches.uuid as batch_uuid', 'batches.name as batch_name', 'courses.uuid as course_uuid', 'courses.name as course_name', 'contacts.father_name', 'contacts.contact_number', 'registrations.uuid as registration_uuid', 'registrations.code_number as registration_code_number', 'registrations.date as registration_date', 'users.name as user_name', 'periods.name as period_name')
            ->with('payments')
            ->whereIn('transactions.head', ['student_fee', 'registration_fee'])
            ->leftJoin('students', function ($join) {
                $join->on('transactions.transactionable_id', '=', 'students.id')
                    ->where('transactions.transactionable_type', '=', 'Student');
            })
            ->leftJoin('registrations', function ($join) {
                $join->on('transactions.transactionable_id', '=', 'registrations.id')
                    ->where('transactions.transactionable_type', '=', 'Registration');
            })
            ->leftJoin('contacts', function ($join) {
                $join->on('contacts.id', '=', \DB::raw("IF(transactions.transactionable_type = 'Student', students.contact_id, registrations.contact_id)"));
            })
            ->leftJoin('admissions', 'students.admission_id', '=', 'admissions.id')
            ->leftJoin('batches', 'students.batch_id', '=', 'batches.id')
            ->leftJoin('courses', function ($join) {
                $join->on('courses.id', '=', \DB::raw("IF(transactions.transactionable_type = 'Student', batches.course_id, registrations.course_id)"));
            })
            ->leftJoin('users', function ($join) {
                $join->on('transactions.user_id', '=', 'users.id');
            })
            ->leftJoin('periods', 'transactions.period_id', '=', 'periods.id')
            ->where('periods.team_id', auth()->user()->current_team_id)
            ->where(function ($q) {
                $q->where(function ($q) {
                    $q->where('transactions.is_online', 0)
                        ->orWhere(function ($q) {
                            $q->where('transactions.is_online', 1)->whereNotNull('transactions.processed_at');
                        });
                })
                    ->whereNull('transactions.cancelled_at')->whereNull('transactions.rejected_at');
            })
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->orderBy('transactions.date', 'asc')
            ->orderBy('transactions.id', 'asc')
            ->get();

        $studentFeePayments = FeePayment::query()
            ->select('student_fee_payments.id', 'student_fee_payments.transaction_id', 'student_fee_payments.fee_head_id',
                'student_fee_payments.default_fee_head', 'student_fee_payments.amount', 'fee_heads.name as fee_head_name')
            ->whereIn('transaction_id', $transactions->pluck('id'))
            ->leftJoin('fee_heads', 'student_fee_payments.fee_head_id', '=', 'fee_heads.id')
            ->get();

        $data = [];

        $row = [
            trans('general.sno'),
            trans('academic.period.period'),
            trans('finance.transaction.props.code_number'),
            trans('finance.transaction.props.date'),
            trans('student.admission.props.code_number'),
            trans('student.props.name'),
            trans('academic.course.course'),
        ];

        $footerRow = [
            trans('finance.fee.total'),
            '',
            '',
            '',
            '',
            '',
            '',
        ];

        $total = [];
        foreach ($paymentMethods as $paymentMethod) {
            $row[] = $paymentMethod->name;
            $total[Str::camel($paymentMethod->name)] = 0;
        }

        array_push($row, trans('finance.transaction.props.instrument_number'));
        array_push($row, trans('finance.transaction.props.reference_number'));
        array_push($row, trans('finance.transaction.props.instrument_date'));
        array_push($row, trans('finance.transaction.props.clearing_date'));
        array_push($row, trans('finance.transaction.props.bank_detail'));

        foreach ($uniqueFeeHeads as $feeHead) {
            $row[] = $feeHead;
            $total[Str::camel($feeHead)] = 0;
        }

        $row[] = trans('finance.fee.total');

        $data[] = $row;

        foreach ($transactions as $index => $transaction) {
            $row = [];
            $row = [
                'sno' => $index + 1,
                'period' => $transaction->period_name,
                'voucher_number' => $transaction->voucher_number,
                'date' => $transaction->date?->formatted,
                'code_number' => $transaction->admission_code_number ?? $transaction->registration_code_number,
                'student_name' => $transaction->name,
                'course' => $transaction->course_name.' - '.$transaction->batch_name,
            ];

            foreach ($paymentMethods as $paymentMethod) {
                $row[Str::camel($paymentMethod->name)] = $transaction->payments->where('payment_method_id', $paymentMethod->id)->sum('amount.value');

                $total[Str::camel($paymentMethod->name)] += $row[Str::camel($paymentMethod->name)];
            }

            foreach ($transaction->payments as $payment) {
                $row['instrument_number'] = Arr::get($payment, 'details.instrument_number');
                $row['reference_number'] = Arr::get($payment, 'details.reference_number');
                $row['instrument_date'] = \Cal::date(Arr::get($payment, 'details.instrument_date'))?->formatted;
                $row['clearing_date'] = \Cal::date(Arr::get($payment, 'details.clearing_date'))?->formatted;
                $row['bank_detail'] = Arr::get($payment, 'details.bank_detail');
            }

            $feePayments = $studentFeePayments->where('transaction_id', $transaction->id);

            foreach ($uniqueFeeHeads as $feeHead) {
                $row[Str::camel($feeHead)] = 0;
            }

            foreach ($feePayments as $feePayment) {
                if (! empty($feePayment->default_fee_head->value)) {
                    $row[Str::camel($feePayment->default_fee_head->value)] = $feePayment->amount->value;

                    $total[Str::camel($feePayment->default_fee_head->value)] += $feePayment->amount->value;
                } else {
                    $row[Str::camel($feePayment->fee_head_name)] = $feePayment->amount->value;

                    $total[Str::camel($feePayment->fee_head_name)] += $feePayment->amount->value;
                }
            }

            $row[] = $transaction->amount->value;

            $data[] = $row;
        }

        foreach ($paymentMethods as $paymentMethod) {
            $footerRow[Str::camel($paymentMethod->name)] = $total[Str::camel($paymentMethod->name)];
        }

        array_push($footerRow, '');
        array_push($footerRow, '');
        array_push($footerRow, '');
        array_push($footerRow, '');
        array_push($footerRow, '');

        foreach ($uniqueFeeHeads as $feeHead) {
            $footerRow[Str::camel($feeHead)] = $total[Str::camel($feeHead)];
        }

        $footerRow[] = $transactions->sum('amount.value');

        $data[] = $footerRow;

        $data = array_merge([[trans('finance.report.detailed_fee_payment')]], $data);

        return Excel::download(new DetailedFeePaymentExport($data), 'Detailed Fee Payment Report.xlsx');
    }
}
