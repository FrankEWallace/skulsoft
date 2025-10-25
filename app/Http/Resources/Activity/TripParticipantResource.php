<?php

namespace App\Http\Resources\Activity;

use Illuminate\Http\Resources\Json\JsonResource;

class TripParticipantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $type = [];

        if ($this->model_type == 'Student') {
            $type = [
                'label' => trans('student.student'),
                'value' => 'student',
            ];
        } elseif ($this->model_type == 'Employee') {
            $type = [
                'label' => trans('employee.employee'),
                'value' => 'employee',
            ];
        }

        $participantUuid = $this->model->uuid;
        $name = $this->model->contact?->name;
        $contactNumber = $this->model->contact?->contact_number;

        return [
            'uuid' => $this->uuid,
            'participant_uuid' => $participantUuid,
            'name' => $name,
            'contact_number' => $contactNumber,
            'type' => $type,
            'amount' => $this->amount,
            'paid' => $this->paid,
            'balance' => \Price::from($this->amount->value - $this->paid->value),
            'payments' => collect($this->getMeta('payments'))->map(function ($payment) {
                return [
                    ...$payment,
                    'amount' => \Price::from($payment['amount']),
                ];
            }),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
