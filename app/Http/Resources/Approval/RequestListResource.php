<?php

namespace App\Http\Resources\Approval;

use App\Enums\Approval\Status;
use App\Http\Resources\Employee\EmployeeSummaryResource;
use App\Http\Resources\OptionResource;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class RequestListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $requester = $request->employees->firstWhere('user_id', $this->request_user_id);

        $approver = $request->employees?->firstWhere('user_id', $this->actionable_user_id);

        return [
            'uuid' => $this->uuid,
            'code_number' => $this->code_number,
            'title' => $this->title,
            'type' => TypeResource::make($this->type),
            'other_team' => $this->type->team_id != auth()->user()->current_team_id ? true : false,
            'priority' => OptionResource::make($this->priority),
            'group' => OptionResource::make($this->group),
            'nature' => OptionResource::make($this->nature),
            'date' => $this->date,
            'status' => Status::getDetail($this->status),
            'amount' => $this->amount,
            'due_date' => $this->due_date,
            'requester' => $requester ? EmployeeSummaryResource::make($requester) : null,
            'approver' => $approver ? EmployeeSummaryResource::make($approver) : null,
            $this->mergeWhen($request->query('processed_requests'), [
                ...$this->getProcessedRecords($request),
            ]),
            'is_editable' => $this->getIsEditable($request),
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }

    private function getProcessedRecords($request)
    {
        $employeeUserId = $request->employee_user_id;

        $requestRecord = $this->requestRecords->firstWhere('user_id', $employeeUserId);

        if (! $requestRecord) {
            return [];
        }

        $duration = null;
        if ($requestRecord->received_at->value && $requestRecord->processed_at->value) {
            $receivedAt = Carbon::parse($requestRecord->received_at->value);
            $processedAt = Carbon::parse($requestRecord->processed_at->value);
            $duration = $processedAt->diffForHumans($receivedAt);
        }

        return [
            'duration' => $duration,
            'comment' => $requestRecord->comment,
            'comment_short' => Str::limit($requestRecord->comment, 50),
            'processed_at' => $requestRecord->processed_at,
            'processed_status' => Status::getDetail($requestRecord->status),
        ];
    }

    private function getIsEditable($request)
    {
        $isEditable = $this->is_editable;

        if ($this->request_user_id == auth()->id()) {
            return $isEditable;
        }

        $approvalType = $this->type;
        $approvalLevels = $approvalType->levels;

        $currentEmployee = $request->current_employee;
        $approvalLevel = $approvalLevels->firstWhere('employee_id', $currentEmployee?->id);

        if (! $approvalLevel) {
            return false;
        }

        $allowedActions = Arr::get($approvalLevel, 'config.actions', []);

        if (! in_array('edit', $allowedActions)) {
            return false;
        }

        return true;
    }
}
