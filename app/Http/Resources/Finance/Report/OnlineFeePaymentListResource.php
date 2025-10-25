<?php

namespace App\Http\Resources\Finance\Report;

use App\Http\Resources\Finance\TransactionPaymentResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class OnlineFeePaymentListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $referenceNumber = Arr::get($this->payment_gateway, 'reference_number');

        $hasStatusCheck = false;
        $statusCheckUrl = null;
        $gatewayName = Arr::get($this->payment_gateway, 'name');
        if (auth()->user()->hasAnyRole(['admin', 'accountant']) && in_array($gatewayName, ['ccavenue', 'billdesk'])) {
            $hasStatusCheck = true;
            $statusCheckUrl = url('payment/'.$gatewayName.'/status?reference_number='.$referenceNumber);
        }

        return [
            'uuid' => $this->uuid,
            'student_uuid' => $this->student_uuid,
            'voucher_number' => $this->voucher_number,
            'amount' => $this->amount,
            'date' => $this->date,
            'ledger_name' => $this->ledger_name,
            'ledger_type' => $this->ledger_type,
            'type' => $this->type,
            'name' => $this->name,
            'father_name' => $this->father_name,
            'code_number' => $this->code_number,
            'joining_date' => \Cal::date($this->joining_date),
            'processed_at' => $this->processed_at,
            'payment' => TransactionPaymentResource::make($this->whenLoaded('payment')),
            $this->mergeWhen(! $this->processed_at->value, [
                'update_status' => Arr::get($this->payment_gateway, 'status', 'pending'),
                'error_code' => Str::toWord(Arr::get($this->payment_gateway, 'code')),
            ]),
            'batch_name' => $this->batch_name,
            'course_name' => $this->course_name,
            'contact_number' => $this->contact_number,
            'user' => [
                'profile' => [
                    'name' => $this->user_name,
                ],
            ],
            $this->mergeWhen($hasStatusCheck, [
                'has_status_check' => $hasStatusCheck,
                'status_check_url' => $statusCheckUrl,
            ]),
            $this->mergeWhen($this->is_online, [
                'is_online' => true,
                'reference_number' => $referenceNumber,
                'gateway' => $gatewayName,
            ]),
            'created_at' => \Cal::dateTime($this->created_at),
        ];
    }
}
