<?php

namespace App\Services\Employee\Payroll;

use App\Actions\Employee\FetchAllEmployee;
use App\Enums\Employee\Payroll\PayrollStatus;
use App\Helpers\CalHelper;
use App\Models\Employee\Payroll\Payroll;
use Illuminate\Http\Request;

class BulkExportPayrollService
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

        $employees = (new FetchAllEmployee)->execute($request);

        // if ($employees->count() > 100) {
        //     abort(398, trans('employee.payroll.max_employee_limit_exceeded_for_salary_sheet'));
        // }

        $payrolls = Payroll::query()
            ->with(['records', 'records.payHead'])
            ->where('start_date', $startDate)
            ->where('end_date', $endDate)
            ->whereIn('employee_id', $employees->pluck('id')->all())
            ->where('status', PayrollStatus::PROCESSED->value)
            ->get();

        $payrolls->map(function ($payroll) use ($employees) {
            $employee = $employees->firstWhere('id', $payroll->employee_id);

            $payroll->employee = $employee;
            $payroll->attendanceSummary = $payroll->getAttendanceSummary();
            $payroll->account = $employee->contact?->accounts()?->first();
        });

        return view()->first([
            'print.custom.employee.payroll.bulk-salary-slip',
            'print.employee.payroll.bulk-salary-slip',
        ], compact('payrolls'));
    }
}
