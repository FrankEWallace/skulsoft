<?php

namespace App\Http\Resources\Student;

use App\Enums\Finance\BankTransferStatus;
use App\Http\Resources\MediaResource;
use App\Http\Resources\UserSummaryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class BankTransferResource extends JsonResource
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
            'code_number' => $this->code_number,
            'amount' => $this->amount,
            'date' => $this->date,
            'status' => BankTransferStatus::getDetail($this->status),
            'requester' => UserSummaryResource::make($this->whenLoaded('requester')),
            'approver' => UserSummaryResource::make($this->whenLoaded('approver')),
            'remarks' => $this->remarks,
            'comment' => $this->comment,
            'processed_at' => $this->processed_at,
            'fee_details' => $this->fee_details,
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
