<?php

namespace App\Services\Employee\Payroll;

use App\Actions\Employee\FetchAllEmployee;
use App\Enums\Employee\Payroll\PayrollStatus;
use App\Helpers\CalHelper;
use App\Models\Account;
use App\Models\Employee\Payroll\Payroll;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PaymentAdviceService
{
    public function generate(Request $request)
    {
        $startDate = CalHelper::validateDate($request->query('salaryStartDate')) ? $request->query('salaryStartDate') : null;
        $endDate = CalHelper::validateDate($request->query('salaryEndDate')) ? $request->query('salaryEndDate') : null;

        if (! $startDate || ! $endDate) {
            abort(404);
        }

        if ($startDate > $endDate) {
            abort(404);
        }

        $request->merge(['paginate' => false]);

        $inclusions = Str::toArray($request->query('inclusions'));

        $employees = (new FetchAllEmployee)->execute($request);

        $payrolls = Payroll::query()
            ->with(['records', 'records.payHead'])
            ->where('start_date', $startDate)
            ->where('end_date', $endDate)
            ->whereIn('uuid', Str::toArray($request->query('items')))
            ->whereIn('employee_id', $employees->pluck('id')->all())
            ->where('status', PayrollStatus::PROCESSED->value)
            ->get();

        $accounts = Account::query()
            ->where('accountable_type', 'Contact')
            ->whereIn('accountable_id', $employees->pluck('contact_id')->all())
            ->get();

        $summary = [];
        $grandTotal = 0;
        foreach ($payrolls as $payroll) {
            $employee = $employees->firstWhere('id', $payroll->employee_id);
            $account = $accounts->firstWhere('accountable_id', $employee->contact_id);

            $summary[] = [
                'name' => $employee->name,
                'code_number' => $employee->code_number,
                'account_number' => $account?->number,
                'account_name' => $account?->name,
                'bank_name' => Arr::get($account?->bank_details ?? [], 'bank_name'),
                'branch_name' => Arr::get($account?->bank_details ?? [], 'branch_name'),
                'bank_code1' => Arr::get($account?->bank_details ?? [], 'bank_code1'),
                'bank_code2' => Arr::get($account?->bank_details ?? [], 'bank_code2'),
                'bank_code3' => Arr::get($account?->bank_details ?? [], 'bank_code3'),
                'net_salary' => $payroll->total,
            ];

            $grandTotal += $payroll->total->value;
        }

        $summary = collect($summary)->sortBy('name')->values()->all();

        $grandTotal = \Price::from($grandTotal);

        $params = [
            'start_date' => \Cal::date($request->query('salaryStartDate')),
            'end_date' => \Cal::date($request->query('salaryEndDate')),
            'payment_advice_period' => $request->query('paymentAdvicePeriod'),
            'payment_advice_bank_details' => $request->query('paymentAdviceBankDetails'),
            'payment_advice_account_details' => $request->query('paymentAdviceAccountDetails'),
        ];

        return view()->first([
            'print.custom.employee.payroll.payment-advice',
            'print.employee.payroll.payment-advice',
        ], compact('payrolls', 'params', 'summary', 'grandTotal'));
    }
}
