<?php

namespace App\Http\Resources\Student;

use App\Enums\Finance\PaymentStatus;
use App\Enums\Student\RegistrationStatus;
use App\Http\Resources\Academic\CourseResource;
use App\Http\Resources\Academic\PeriodResource;
use App\Http\Resources\ContactResource;
use App\Http\Resources\Employee\EmployeeSummaryResource;
use App\Http\Resources\Finance\TransactionResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\OptionResource;
use Illuminate\Http\Resources\Json\JsonResource;

class RegistrationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'uuid' => $this->uuid,
            'student_uuid' => $request->student_uuid,
            'number_format' => $this->number_format,
            'number' => $this->number,
            'code_number' => $this->code_number,
            'is_provisional' => (bool) $this->is_provisional,
            'admission_number' => $this->admission_number,
            'batch_name' => $this->batch_name,
            'admission_date' => \Cal::date($this->admission_date),
            'contact' => new ContactResource($this->whenLoaded('contact')),
            'period' => new PeriodResource($this->whenLoaded('period')),
            'course' => new CourseResource($this->whenLoaded('course')),
            'admission' => new AdmissionResource($this->whenLoaded('admission')),
            'employee' => EmployeeSummaryResource::make($this->whenLoaded('employee')),
            'date' => $this->date,
            'fee' => $this->fee,
            'enrollment_type' => OptionResource::make($this->whenLoaded('enrollmentType')),
            'stage' => OptionResource::make($this->whenLoaded('stage')),
            'payment_status' => PaymentStatus::getDetail($this->payment_status),
            'status' => RegistrationStatus::getDetail($this->status),
            'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),
            $this->mergeWhen($this->relationLoaded('transactions'), [
                ...$this->getPaidAmount(),
            ]),
            'remarks' => $this->remarks,
            'is_online' => $this->is_online,
            $this->mergeWhen($this->is_online, [
                'application_number' => $this->getMeta('application_number'),
            ]),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            $this->mergeWhen($this->status === RegistrationStatus::REJECTED, [
                'rejection_remarks' => $this->rejection_remarks,
                'rejected_at' => $this->rejected_at,
            ]
            ),
            $this->mergeWhen($request->has_custom_fields, [
                'custom_fields' => $this->getCustomFieldsValues(),
            ]),
            'payment_due_date' => \Cal::date($this->getMeta('payment_due_date')),
            'is_converted' => $this->is_converted,
            $this->mergeWhen($this->is_converted, [
                'enquiry' => [
                    'uuid' => $this->getMeta('enquiry_uuid'),
                ],
            ]),
            'is_editable' => $this->isEditable(),
            'is_deletable' => $this->isEditable(),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }

    private function getPaidAmount(): array
    {
        $paid = $this->transactions->filter(function ($transaction) {
            return empty($transaction->cancelled_at->value) && empty($transaction->rejected_at->value) && (
                ! $transaction->is_online || ($transaction->is_online && ! empty($transaction->processed_at->value))
            );
        })->sum('amount.value');

        $balance = $this->fee->value - $paid;

        return [
            'paid' => \Price::from($paid),
            'balance' => \Price::from($balance),
        ];
    }
}
