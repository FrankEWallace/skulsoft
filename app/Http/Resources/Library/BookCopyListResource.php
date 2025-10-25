<?php

namespace App\Http\Resources\Library;

use App\Enums\Library\HoldStatus;
use App\Http\Resources\Employee\EmployeeSummaryResource;
use App\Http\Resources\OptionResource;
use App\Http\Resources\Student\StudentSummaryResource;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class BookCopyListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $book = $request->books->firstWhere('id', $this->book_id);

        $requester = [];
        $issueTo = null;

        if ($this->transactionable_type === 'Student') {
            $requester = $request->students->firstWhere('id', $this->transactionable_id);
            $issueTo = 'student';
        } elseif ($this->transactionable_type === 'Employee') {
            $requester = $request->employees->firstWhere('id', $this->transactionable_id);
            $issueTo = 'employee';
        }

        $date = $request->date;
        $overdue = 0;
        if ($this->issue_date && $this->due_date) {
            $dueDate = $this->due_date;

            if (empty($dueDate?->value)) {
                $overdue = 0;
            } else {
                $overdue = $date > $dueDate->value ? abs(Carbon::parse($date)->diffInDays($dueDate->carbon())) : 0;
            }
        }

        $issueStatus = [
            'label' => trans('library.transaction.statuses.available'),
            'value' => 'available',
        ];
        if ($this->transactionable_type && ! $this->return_date) {
            $issueStatus = [
                'label' => trans('library.transaction.statuses.issued'),
                'value' => 'issued',
            ];
        }

        if (! empty($this->hold_status?->value)) {
            $issueStatus = [
                'label' => trans('library.transaction.statuses.hold'),
                'value' => 'hold',
            ];
        }

        return [
            'uuid' => $this->uuid,
            'number' => $this->number,
            'book' => BookResource::make($book),
            'condition' => OptionResource::make($this->whenLoaded('condition')),
            'vendor' => $this->vendor,
            'invoice_number' => $this->invoice_number,
            'invoice_date' => $this->invoice_date,
            'room_number' => $this->room_number,
            'rack_number' => $this->rack_number,
            'shelf_number' => $this->shelf_number,
            'location' => $this->location,
            'price' => $this->price,
            'hold_status' => empty($this->hold_status?->value) ? [
                'label' => trans('library.book_copy.statuses.stock'),
                'value' => 'stock',
            ] : HoldStatus::getDetail($this->hold_status),
            'transaction_code_number' => $this->transaction_code_number,
            'issue_date' => \Cal::date($this->issue_date),
            'due_date' => \Cal::date($this->due_date),
            'issue_status' => $issueStatus,
            'issued_to' => trans($issueTo.'.'.$issueTo),
            'overdue' => $overdue,
            'book_addition_date' => \Cal::date($this->book_addition_date),
            $this->mergeWhen($this->transactionable_type == 'Student', [
                'requester' => StudentSummaryResource::make($requester),
                'requester_detail' => Arr::get($requester, 'course_name').' '.Arr::get($requester, 'batch_name'),
            ]),
            $this->mergeWhen($this->transactionable_type == 'Employee', [
                'requester' => EmployeeSummaryResource::make($requester),
                'requester_detail' => Arr::get($requester, 'designation_name'),
            ]),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
