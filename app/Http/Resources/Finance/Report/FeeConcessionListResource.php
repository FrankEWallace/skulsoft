<?php

namespace App\Http\Resources\Finance\Report;

use App\Enums\Finance\DefaultFeeHead;
use App\Http\Resources\Finance\FeeHeadResource;
use Illuminate\Http\Resources\Json\JsonResource;

class FeeConcessionListResource extends JsonResource
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
            'name' => $this->name,
            'father_name' => $this->father_name,
            'code_number' => $this->code_number,
            'roll_number' => $this->roll_number,
            'concession_name' => $this->concession_name ?? trans('finance.fee_concession.custom_concession'),
            'concession_type' => $this->concession_type,
            'joining_date' => \Cal::date($this->joining_date),
            'batch_name' => $this->batch_name,
            'course_name' => $this->course_name,
            'contact_number' => $this->contact_number,
            'installment_title' => $this->installment_title,
            'fee_group_name' => $this->fee_group_name,
            $this->mergeWhen($this->relationLoaded('records'), [
                'records' => $this->getRecords(),
            ]),
        ];
    }

    private function getRecords()
    {
        $records = [];

        foreach ($this->records as $record) {
            $feePayments = $this->payments->where('student_fee_id', $record->student_fee_id);

            if ($record->fee_head_id) {
                $feePayments = $feePayments->where('fee_head_id', $record->fee_head_id);
            } else {
                $feePayments = $feePayments->where('default_fee_head', $record->default_fee_head);
            }

            $records[] = [
                'uuid' => $record->uuid,
                'head' => FeeHeadResource::make($record->head),
                'default_fee_head' => DefaultFeeHead::getDetail($record->default_fee_head),
                'fee_head_name' => $record->fee_head_id ? $record->head->name : DefaultFeeHead::getLabel($record->default_fee_head->value),
                'amount' => $record->amount,
                'amount_with_concession' => $record->getAmountWithConcession(),
                'paid' => $record->paid,
                'concession' => $record->concession,
                'concession_given' => \Price::from($feePayments->sum('concession_amount.value')),
                'has_custom_amount' => (bool) $record->has_custom_amount,
                'due_date' => $record->due_date,
                'is_optional' => (bool) $record->is_optional,
                'remarks' => $record->remarks,
                'created_at' => \Cal::dateTime($record->created_at),
                'updated_at' => \Cal::dateTime($record->updated_at),
            ];
        }

        return $records;
    }
}
