<?php

namespace App\Http\Resources\Finance\Report;

use App\Helpers\CalHelper;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class InstallmentWiseFeeDueListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $dueOn = $request->query('due_on');

        if (! CalHelper::validateDate($dueOn)) {
            $dueOn = today();
        } else {
            $dueOn = Carbon::parse($dueOn);
        }

        $dueDate = Carbon::parse($this->final_due_date);

        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'father_name' => $this->father_name,
            'code_number' => $this->code_number,
            'roll_number' => $this->roll_number,
            'joining_date' => \Cal::date($this->joining_date),
            'batch_name' => $this->batch_name,
            'course_name' => $this->course_name,
            'contact_number' => $this->contact_number,
            'installment_title' => $this->installment_title,
            'fee_group_name' => $this->fee_group_name,
            'due_fee' => \Price::from($this->due_fee),
            'final_due_date' => \Cal::date($this->final_due_date),
            'overdue_by' => abs($dueOn->diffInDays($dueDate)).' '.trans('list.durations.days'),
        ];
    }
}
