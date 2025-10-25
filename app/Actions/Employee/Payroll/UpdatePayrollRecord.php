<?php

namespace App\Actions\Employee\Payroll;

use App\Enums\Employee\Payroll\PayrollStatus;
use App\Enums\Finance\PaymentStatus;
use App\Models\Employee\Payroll\Payroll;
use App\Models\Employee\Payroll\Record;
use Illuminate\Support\Arr;

class UpdatePayrollRecord
{
    public function execute(Payroll $payroll, array $params = []): void
    {
        $records = Arr::get($params, 'records', []);

        $data = (new FetchPayrollRecord)->execute($params);

        // if not passed then get it from fetch payroll record
        if (empty($records)) {
            $records = Arr::get($data, 'records', []);
        }

        $workingDays = Arr::get($data, 'workingDays', 0);
        $calculatedRecords = Arr::get($data, 'records', []);
        $calculatedAttendances = Arr::get($data, 'attendanceSummary', []);
        $workingDays = Arr::get($data, 'workingDays', 0);
        $attendances = Arr::map($calculatedAttendances, function ($item) {
            return ['code' => Arr::get($item, 'code'), 'count' => Arr::get($item, 'count'), 'unit' => Arr::get($item, 'unit')];
        });

        $earningComponent = Arr::get($data, 'netEarning');
        $deductionComponent = Arr::get($data, 'netDeduction');
        $employeeContribution = Arr::get($data, 'employeeContribution');
        $employerContribution = Arr::get($data, 'employerContribution');
        $netSalary = $earningComponent - $deductionComponent - $employeeContribution;

        $calculated = [
            'earning' => $earningComponent,
            'deduction' => $deductionComponent,
            'employee_contribution' => $employeeContribution,
            'employer_contribution' => $employerContribution,
            'salary' => $netSalary,
        ];

        // if not passed then use calculated
        $actual = Arr::get($params, 'actual', $calculated);

        $meta = $payroll->meta;
        $meta['attendances'] = $attendances;
        $meta['working_days'] = $workingDays;
        $meta['calculated'] = $calculated;
        $meta['actual'] = $actual;

        if (Arr::get($params, 'has_hourly_payroll')) {
            $meta['has_hourly_payroll'] = true;
        }

        $payroll->meta = $meta;

        $payroll->total = Arr::get($actual, 'salary');
        $payroll->status = PayrollStatus::PROCESSED->value;
        $payroll->payment_status = PaymentStatus::UNPAID->value;
        $payroll->save();

        if (Arr::get($params, 'has_hourly_payroll')) {
            $payroll->records()->delete();

            return;
        }

        foreach ($records as $record) {
            $calculatedRecord = Arr::first($calculatedRecords, function ($item) use ($record) {
                return Arr::get($item, 'pay_head.uuid') == Arr::get($record, 'pay_head.uuid');
            });

            $payrollRecord = Record::firstOrCreate([
                'payroll_id' => $payroll->id,
                'pay_head_id' => Arr::get($record, 'pay_head.id'),
            ]);

            $payrollRecord->calculated = Arr::get($calculatedRecord, 'amount');
            $payrollRecord->amount = Arr::get($record, 'amount');

            $meta = $payrollRecord->meta;
            $meta['as_total'] = (bool) Arr::get($record, 'pay_head.as_total', false);
            $payrollRecord->meta = $meta;

            $payrollRecord->save();
        }

        $salaryStructure = Arr::get($params, 'salary_structure');
        $payroll->salary_structure_id = $salaryStructure->id;
        $payroll->save();
    }
}
