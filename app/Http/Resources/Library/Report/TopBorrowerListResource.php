<?php

namespace App\Http\Resources\Library\Report;

use App\Http\Resources\Employee\EmployeeSummaryResource;
use App\Http\Resources\Student\StudentSummaryResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class TopBorrowerListResource extends JsonResource
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
        $issueTo = null;

        if ($this->transactionable_type === 'Student') {
            $requester = $request->students->firstWhere('id', $this->transactionable_id);
            $issueTo = 'student';
        } elseif ($this->transactionable_type === 'Employee') {
            $requester = $request->employees->firstWhere('id', $this->transactionable_id);
            $issueTo = 'employee';
        }

        return [
            'issue_to' => trans($issueTo.'.'.$issueTo),
            'count' => $this->count,
            $this->mergeWhen($this->transactionable_type == 'Student', [
                'requester' => StudentSummaryResource::make($requester),
                'requester_detail' => Arr::get($requester, 'course_name').' '.Arr::get($requester, 'batch_name'),
            ]),
            $this->mergeWhen($this->transactionable_type == 'Employee', [
                'requester' => EmployeeSummaryResource::make($requester),
                'requester_detail' => Arr::get($requester, 'designation_name'),
            ]),
        ];
    }
}
