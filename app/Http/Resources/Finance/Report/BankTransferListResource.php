<?php

namespace App\Http\Resources\Finance\Report;

use App\Enums\Finance\BankTransferStatus;
use App\Http\Resources\UserSummaryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class BankTransferListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $transaction = $request->transactions->firstWhere('meta.bank_transfer_id', $this->id);

        $details = [];

        if ($this->model_type == 'Student') {
            $student = $request->students->firstWhere('id', $this->model_id);
            $details = [
                'type' => 'student',
                'name' => $student->name,
                'code_number' => $student->code_number,
                'batch_name' => $student->batch_name,
                'course_name' => $student->course_name,
                'father_name' => $student->father_name,
                'contact_number' => $student->contact_number,
            ];
        }

        return [
            'uuid' => $this->uuid,
            'code_number' => $this->code_number,
            'transaction' => [
                'uuid' => $transaction?->uuid,
                'voucher_number' => $transaction?->code_number,
            ],
            'details' => $details,
            'amount' => $this->amount,
            'date' => $this->date,
            'status' => BankTransferStatus::getDetail($this->status),
            'remarks' => $this->remarks,
            'comment' => $this->comment,
            'requester' => UserSummaryResource::make($this->whenLoaded('requester')),
            'approver' => UserSummaryResource::make($this->whenLoaded('approver')),
            'processed_at' => $this->processed_at,
        ];
    }
}
