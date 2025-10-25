<?php

namespace App\Http\Resources\Employee\Payroll;

use App\Http\Resources\Employee\EmployeeSummaryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaryStructureResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'uuid' => $this->uuid,
            'employee' => EmployeeSummaryResource::make($this->whenLoaded('employee')),
            'effective_date' => $this->effective_date,
            'template' => SalaryTemplateResource::make($this->whenLoaded('template')),
            'records' => $this->new_records,
            'hourly_pay' => $this->hourly_pay,
            'net_earning' => $this->net_earning,
            'net_deduction' => $this->net_deduction,
            'calculated_net_deduction' => \Price::from($this->net_deduction->value + $this->net_employee_contribution->value),
            'net_employee_contribution' => $this->net_employee_contribution,
            'net_employer_contribution' => $this->net_employer_contribution,
            'net_salary' => $this->net_salary,
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
