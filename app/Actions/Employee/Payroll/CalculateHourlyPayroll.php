<?php

namespace App\Actions\Employee\Payroll;

use App\Enums\Employee\Payroll\PayHeadCategory;
use App\Helpers\SysHelper;
use App\Models\Employee\Attendance\Timesheet;
use App\Models\Employee\Payroll\SalaryStructure;
use Carbon\Carbon;

class CalculateHourlyPayroll
{
    public function execute(int $employeeId, string $startDate, string $endDate, SalaryStructure $salaryStructure): array
    {
        $timesheets = Timesheet::query()
            ->where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $totalWorkDuration = 0;
        foreach ($timesheets as $timesheet) {
            $totalWorkDuration += abs(Carbon::parse($timesheet->out_at->value)->diffInMinutes($timesheet->in_at->value));
        }

        $totalWorkingHours = round($totalWorkDuration / 60, 2);

        $hourlyPay = $salaryStructure->hourly_pay->value;

        $netEarning = SysHelper::formatAmount($totalWorkingHours * $hourlyPay);

        $attendanceSummary = [
            [
                'code' => 'WH',
                'name' => trans('employee.payroll.salary_structure.working_hours'),
                'count' => $totalWorkingHours,
                'unit' => 'hours',
            ],
        ];

        $payHeads = [
            [
                'id' => null,
                'uuid' => null,
                'name' => trans('employee.payroll.salary_structure.props.hourly_pay'),
                'type' => null,
                'category' => PayHeadCategory::EARNING->value,
                'code' => 'WHP',
                'amount' => $netEarning,
                'as_total' => false,
            ],
        ];

        $data['pay_heads'] = $payHeads;
        $data['earning_component'] = $netEarning;
        $data['deduction_component'] = 0;
        $data['employee_contribution'] = 0;
        $data['employer_contribution'] = 0;
        $data['net_salary'] = $netEarning;

        return [$attendanceSummary, $data];
    }
}
