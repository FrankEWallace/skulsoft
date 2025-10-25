<?php

namespace App\Http\Resources\Employee\Payroll;

use App\Enums\Employee\Payroll\PayHeadCategory;
use App\Enums\Employee\Payroll\PayrollStatus;
use App\Enums\Finance\PaymentStatus;
use App\Http\Resources\Employee\EmployeeSummaryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $actualDeduction = is_numeric($this->getMeta('actual.deduction')) ? $this->getMeta('actual.deduction') : 0;
        $actualEmployeeContribution = is_numeric($this->getMeta('actual.employee_contribution')) ? $this->getMeta('actual.employee_contribution') : 0;

        $netDeductionWithEmployeeContribution = $actualDeduction + $actualEmployeeContribution;

        return [
            'uuid' => $this->uuid,
            'code_number' => $this->code_number,
            'salary_structure' => SalaryStructureResource::make($this->whenLoaded('salaryStructure')),
            'employee' => EmployeeSummaryResource::make($this->whenLoaded('employee')),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'period' => $this->period,
            'duration' => $this->duration,
            'total' => $this->total,
            'paid' => $this->paid,
            'is_paid' => $this->total->value > $this->paid->value ? false : true,
            'has_hourly_payroll' => (bool) $this->getMeta('has_hourly_payroll'),
            'is_processed' => $this->status == PayrollStatus::PROCESSED,
            'status' => PayrollStatus::getDetail($this->status),
            'payment_status' => PaymentStatus::getDetail($this->payment_status),
            'error_message' => $this->getMeta('error_message'),
            'batch_uuid' => $this->getMeta('batch_uuid'),
            'remarks' => $this->remarks,
            'net_earning' => \Price::from($this->getMeta('actual.earning')),
            'net_deduction' => \Price::from($this->getMeta('actual.deduction')),
            'net_deduction_with_employee_contribution' => \Price::from($netDeductionWithEmployeeContribution),
            'employee_contribution' => \Price::from($this->getMeta('actual.employee_contribution')),
            'employer_contribution' => \Price::from($this->getMeta('actual.employer_contribution')),
            'net_salary' => \Price::from($this->getMeta('actual.earning') - $this->getMeta('actual.deduction') - $this->getMeta('actual.employee_contribution')),
            $this->mergeWhen($request->show_attendance_summary, [
                'attendance_summary' => $this->getAttendanceSummary(),
            ]),
            $this->mergeWhen($this->getMeta('has_hourly_payroll'), [
                'records' => [
                    [
                        'amount' => \Price::from($this->getMeta('actual.earning')),
                        'pay_head' => [
                            'name' => trans('employee.payroll.salary_structure.props.hourly_pay'),
                            'category' => PayHeadCategory::getDetail('earning'),
                            'code' => 'WHP',
                        ],
                    ],
                ],
            ]),
            $this->mergeWhen(! $this->getMeta('has_hourly_payroll'), [
                'records' => RecordResource::collection($this->whenLoaded('records')),
            ]),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
