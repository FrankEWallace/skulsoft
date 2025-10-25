<?php

namespace App\Http\Resources\Student;

use App\Enums\Finance\PaymentStatus;
use App\Enums\Student\RegistrationStatus;
use App\Http\Resources\Academic\CourseResource;
use App\Http\Resources\Academic\PeriodResource;
use App\Http\Resources\ContactResource;
use App\Http\Resources\Finance\TransactionResource;
use App\Http\Resources\MediaResource;
use Illuminate\Http\Resources\Json\JsonResource;

class OnlineRegistrationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // $depositFees = $this->getConfig('deposit_fees') ?? [];

        // $totalDepositFee = collect($depositFees)->sum('amount');
        // $paidDepositFee = collect($depositFees)->sum('paid');
        // $balanceDepositFee = $totalDepositFee - $paidDepositFee;

        return [
            'uuid' => $this->uuid,
            'number_format' => $this->number_format,
            'number' => $this->number,
            'code_number' => $this->code_number,
            'contact' => new ContactResource($this->whenLoaded('contact')),
            'period' => new PeriodResource($this->whenLoaded('period')),
            'course' => new CourseResource($this->whenLoaded('course')),
            'admission' => new AdmissionResource($this->whenLoaded('admission')),
            'date' => $this->date,
            'fee' => $this->fee,
            'payment_status' => PaymentStatus::getDetail($this->payment_status),
            'status' => RegistrationStatus::getDetail($this->status),
            'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),
            'remarks' => $this->remarks,
            'is_online' => $this->is_online,
            $this->mergeWhen($this->is_online, [
                // 'deposit_fees' => $depositFees,
                // 'total_deposit_fee' => \Price::from($totalDepositFee),
                // 'paid_deposit_fee' => \Price::from($paidDepositFee),
                // 'balance_deposit_fee' => \Price::from($balanceDepositFee),
                'application_number' => $this->getMeta('application_number'),
            ]),
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            $this->mergeWhen($this->status === RegistrationStatus::REJECTED, [
                'rejection_remarks' => $this->rejection_remarks,
                'rejected_at' => $this->rejected_at,
            ]
            ),
            'is_editable' => $this->isEditable(),
            'is_deletable' => $this->isEditable(),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
