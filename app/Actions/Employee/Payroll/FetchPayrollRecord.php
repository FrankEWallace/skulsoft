<?php

namespace App\Actions\Employee\Payroll;

use Illuminate\Support\Arr;

class FetchPayrollRecord
{
    public function execute(array $params = []): array
    {
        [$attendanceSummary, $data] = (new CalculatePayroll)->execute(
            employeeId: Arr::get($params, 'employee_id'),
            startDate: Arr::get($params, 'start_date'),
            endDate: Arr::get($params, 'end_date'),
            salaryStructure: Arr::get($params, 'salary_structure'),
            attendanceTypes: Arr::get($params, 'attendance_types'),
            productionAttendanceTypes: Arr::get($params, 'production_attendance_types'),
            params: [
                'ignore_attendance' => (bool) Arr::get($params, 'ignore_attendance'),
            ]
        );

        $isBatchProcess = (bool) Arr::get($params, 'is_batch_process');

        $netEarning = Arr::get($data, 'earning_component');
        $netDeduction = Arr::get($data, 'deduction_component');
        $employeeContribution = Arr::get($data, 'employee_contribution');
        $employerContribution = Arr::get($data, 'employer_contribution');
        $records = Arr::get($data, 'pay_heads');
        $workingDays = Arr::get($data, 'working_days');

        $records = collect($records)->filter(function ($record) {
            return $record['category'] != 'component' && $record['category'] != 'gross';
        })->map(function ($record) use ($isBatchProcess) {
            if (! $isBatchProcess) {
                return Arr::except($record, ['id']);
            }

            return $record;
        })->map(function ($record) {
            return [
                'amount' => $record['amount'],
                'pay_head' => [
                    ...Arr::except($record, ['amount']),
                ],
            ];
        })->values()->all();

        $netSalary = $netEarning - $netDeduction - $employeeContribution;

        return compact('attendanceSummary', 'records', 'netEarning', 'netDeduction', 'netSalary', 'employeeContribution', 'employerContribution', 'workingDays');
    }
}
