<?php

namespace App\Actions\Employee\Payroll;

use App\Models\Employee\Payroll\SalaryStructure;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CalculatePayroll
{
    public function execute(int $employeeId, string $startDate, string $endDate, SalaryStructure $salaryStructure, Collection $attendanceTypes, Collection $productionAttendanceTypes, array $params = []): array
    {
        if ($salaryStructure->template->has_hourly_payroll) {
            return (new CalculateHourlyPayroll)->execute(
                employeeId: $employeeId,
                startDate: $startDate,
                endDate: $endDate,
                salaryStructure: $salaryStructure
            );
        }

        $attendance = (new GetAttendanceBetweenPeriod)->execute(
            employeeId: $employeeId,
            startDate: $startDate,
            endDate: $endDate,
            attendanceTypes: $attendanceTypes,
            params: $params
        );

        $attendanceRecords = (new GetProductionAttendanceBetweenPeriod)->execute(
            employeeId: $employeeId,
            startDate: $startDate,
            endDate: $endDate
        );

        $attendanceSummary = (new GetAttendanceSummary)->execute(
            attendance: $attendance,
            attendanceTypes: $attendanceTypes
        );

        $attendanceSummary = (new GetProductionAttendanceSummary)->execute(
            attendanceRecords: $attendanceRecords,
            productionAttendanceTypes: $productionAttendanceTypes,
            attendanceSummary: $attendanceSummary
        );

        $present = (new CalculatePresent)->execute(
            attendance: $attendance,
            attendanceTypes: $attendanceTypes
        );

        $params = [
            'monthly_days' => Carbon::parse($startDate)->daysInMonth,
            'working_days' => $present,
            'attendance_records' => $attendanceRecords,
        ];

        $salaryTemplate = $salaryStructure->template;

        $records = [];
        foreach ($salaryStructure->records as $record) {
            $payHead = $salaryTemplate->records->firstWhere('pay_head_id', $record->pay_head_id)?->payHead;
            $records[] = [
                'pay_head' => $payHead?->uuid,
                'amount' => $record->amount->value,
            ];
        }

        $params['gross_earning'] = $salaryStructure->net_earning->value;
        $params['gross_deduction'] = $salaryStructure->net_deduction->value;

        $data = (new CalculatePayHeads)->execute(
            salaryTemplate: $salaryStructure->template,
            records: $records,
            params: $params
        );

        $data['working_days'] = $present;

        return [$attendanceSummary, $data];

        // $records = (new GetFlatRatePayHeadRecord)->execute(
        //     salaryStructure: $salaryStructure,
        //     startDate: $startDate,
        //     endDate: $endDate
        // );

        // $records = (new GetAttendanceBasedPayHeadRecord)->execute(
        //     salaryStructure: $salaryStructure,
        //     present: $present,
        //     records: $records
        // );

        // $records = (new GetComputationPayHeadRecord)->execute(
        //     salaryStructure: $salaryStructure,
        //     records: $records
        // );

        // $records = (new GetProductionBasedPayHeadRecord)->execute(
        //     salaryStructure: $salaryStructure,
        //     attendanceRecords: $attendanceRecords,
        //     records: $records
        // );

        // $records = (new GetUserDefinedPayHeadRecord)->execute(
        //     salaryStructure: $salaryStructure,
        //     records: $records
        // );

        // return [$attendanceSummary, $records];
    }
}
