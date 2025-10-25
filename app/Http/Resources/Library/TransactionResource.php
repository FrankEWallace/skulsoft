<?php

namespace App\Http\Resources\Library;

use App\Helpers\CalHelper;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $isReturned = false;

        if ($this->relationLoaded('records')) {
            $isReturned = $this->records->filter(function ($record) {
                return empty($record->return_date->value);
            })
                ->count() ? false : true;
        }

        $date = today()->toDateString();

        $overdue = 0;
        if (! $isReturned) {
            if (! CalHelper::validateDate($date)) {
                $date = today()->toDateString();
            }

            $dueDate = $this->due_date;

            if (empty($dueDate?->value)) {
                $overdue = 0;
            } else {
                $overdue = $date > $dueDate->value ? abs(Carbon::parse($date)->diffInDays($dueDate->carbon())) : 0;
            }
        }

        return [
            'uuid' => $this->uuid,
            'code_number' => $this->code_number,
            'records_count' => $this->records_count,
            'non_returned_books_count' => $this->non_returned_books_count,
            'to' => [
                'label' => $this->transactionable_type,
                'value' => Str::lower($this->transactionable_type),
            ],
            'requester' => [
                'uuid' => $this->transactionable?->uuid,
                'name' => $this->transactionable?->contact->name,
                'contact_number' => $this->transactionable?->contact->contact_number,
            ],
            'issue_date' => $this->issue_date,
            'due_date' => $this->due_date,
            'records' => TransactionRecordResource::collection($this->whenLoaded('records')),
            $this->mergeWhen($this->whenLoaded('records'), [
                'is_returned' => $isReturned,
            ]),
            'overdue' => $overdue,
            'remarks' => $this->remarks,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
