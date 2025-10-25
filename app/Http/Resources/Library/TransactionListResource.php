<?php

namespace App\Http\Resources\Library;

use App\Http\Resources\Employee\EmployeeSummaryResource;
use App\Http\Resources\Student\StudentSummaryResource;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class TransactionListResource extends JsonResource
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

        $isReturned = false;

        if ($this->relationLoaded('records')) {
            $isReturned = $this->records->filter(function ($record) {
                return empty($record->return_date->value);
            })
                ->count() ? false : true;
        }

        $date = today()->toDateString();

        $dueInDays = 0;
        $dueDate = $this->due_date->value;

        $dueInDays = Carbon::parse(today()->toDateString())->diffInDays($dueDate);

        if ($this->non_returned_books_count == 0) {
            $dueInDays = 0;
        }

        $isOverdue = false;
        if ($this->non_returned_books_count > 0 && $dueDate < today()->toDateString()) {
            $dueInDays = abs($dueInDays);
            $isOverdue = true;
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
            $this->mergeWhen($this->transactionable_type == 'Student', [
                'requester' => StudentSummaryResource::make($requester),
                'requester_detail' => Arr::get($requester, 'course_name').' '.Arr::get($requester, 'batch_name'),
            ]),
            $this->mergeWhen($this->transactionable_type == 'Employee', [
                'requester' => EmployeeSummaryResource::make($requester),
                'requester_detail' => Arr::get($requester, 'designation_name'),
            ]),
            'issue_date' => $this->issue_date,
            'due_date' => $this->due_date,
            'records' => TransactionRecordResource::collection($this->whenLoaded('records')),
            $this->mergeWhen($this->whenLoaded('records'), [
                'is_returned' => $isReturned,
            ]),
            'is_overdue' => $isOverdue,
            'due_in_days' => $isOverdue ? trans('library.transaction.overdue_by', ['attribute' => $dueInDays]) : $dueInDays,
            'remarks' => $this->remarks,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
