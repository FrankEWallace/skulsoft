<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentFeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $total = $this->getTotal();
        $paid = $this->getPaid();
        $balance = $this->getBalance();

        $dueDate = \Cal::date($this->final_due_date);
        $overdueDays = $this->getOverdueDays();
        $percent = $total->value ? round(($paid->value / $total->value) * 100, 2) : 0;

        return [
            'uuid' => $this->uuid,
            'student_uuid' => $this->student_uuid,
            'total' => $total,
            'paid' => $paid,
            'balance' => $balance,
            'overdue_days' => $overdueDays,
            'percent' => $percent,
            'percentage' => \Percent::from($percent)->formatted,
            'color' => \Percent::from($percent)->getPercentageColor(),
            'due_date' => $dueDate,
            'due_on' => trans('finance.fee_structure.due_on', ['attribute' => $dueDate->formatted]),
            'overdue_by' => trans('finance.fee_structure.overdue_by', ['attribute' => $overdueDays]),
            'installment_title' => $this->installment_title,
            'fee_group_name' => $this->fee_group_name,
        ];
    }
}
