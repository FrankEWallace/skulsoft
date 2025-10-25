<?php

namespace App\Http\Resources\Library;

use App\Enums\Library\ReturnStatus;
use App\Http\Resources\Employee\EmployeeSummaryResource;
use App\Http\Resources\OptionResource;
use App\Http\Resources\Student\StudentSummaryResource;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class BookWiseTransactionListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $requester = [];

        if ($this->transactionable_type === 'Student') {
            $requester = $request->students->firstWhere('id', $this->transactionable_id);
        } elseif ($this->transactionable_type === 'Employee') {
            $requester = $request->employees->firstWhere('id', $this->transactionable_id);
        }

        $isReturned = $this->return_date->value ? true : false;

        $dueInDays = 0;
        $dueDate = $this->due_date;

        $dueInDays = Carbon::parse(today()->toDateString())->diffInDays($dueDate);

        if ($isReturned) {
            $dueInDays = 0;
        }

        $isOverdue = false;
        if ($dueDate < today()->toDateString()) {
            $dueInDays = abs($dueInDays);
            $isOverdue = true;
        }

        return [
            'uuid' => $this->uuid,
            'transaction_uuid' => $this->transaction_uuid,
            'code_number' => $this->code_number,
            'to' => [
                'label' => $this->transactionable_type,
                'value' => Str::lower($this->transactionable_type),
            ],
            $this->mergeWhen($this->transactionable_type == 'Student', [
                'requester' => StudentSummaryResource::make($requester),
                'requester_detail' => Arr::get($requester, 'course_name').' '.Arr::get($requester, 'batch_name'),
            ]),
            $this->mergeWhen($this->transactionable_type == 'Employee', [
                'requester' => EmployeeSummaryResource::make($requester),
                'requester_detail' => Arr::get($requester, 'designation_name'),
            ]),
            'is_overdue' => $isOverdue,
            'due_in_days' => $isOverdue ? trans('library.transaction.overdue_by', ['attribute' => $dueInDays]) : $dueInDays,
            'book_title' => $this->book_title,
            'book_copy_number' => $this->book_copy_number,
            'issue_date' => \Cal::date($this->issue_date),
            'due_date' => \Cal::date($this->due_date),
            'charge' => \Price::from(collect($this->charges)->sum('amount')),
            'return_status' => ReturnStatus::getDetail($this->return_status),
            'condition' => OptionResource::make($this->whenLoaded('condition')),
            'return_date' => $this->return_date,
            'is_returned' => $isReturned,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
