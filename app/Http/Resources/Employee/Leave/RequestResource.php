<?php

namespace App\Http\Resources\Employee\Leave;

use App\Enums\Employee\Leave\RequestStatus;
use App\Helpers\CalHelper;
use App\Http\Resources\Employee\EmployeeSummaryResource;
use App\Http\Resources\Employee\Leave\AllocationResource as LeaveAllocationResource;
use App\Http\Resources\Employee\Leave\TypeResource as LeaveTypeResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\UserSummaryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class RequestResource extends JsonResource
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
            'employee' => EmployeeSummaryResource::make($this->whenLoaded('model')),
            'leave_type' => LeaveTypeResource::make($this->whenLoaded('type')),
            'requester' => UserSummaryResource::make($this->whenLoaded('requester')),
            'is_half_day' => (bool) $this->is_half_day,
            $this->mergeWhen($this->status == RequestStatus::PARTIALLY_APPROVED, [
                'dates' => $this->getPartiallyApprovedDates(),
            ]),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'period' => $this->period,
            'duration' => $this->duration,
            'reason' => $this->reason,
            'comment' => $this->comment,
            'status' => RequestStatus::getDetail($this->status),
            'records' => RequestRecordResource::collection($this->whenLoaded('records')),
            $this->mergeWhen($request->has_leave_allocation, [
                'allocation' => LeaveAllocationResource::make($this->allocation),
            ]),
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }

    private function getPartiallyApprovedDates()
    {
        $allDates = CalHelper::datesInPeriod($this->start_date->value, $this->end_date->value);

        $dates = $this->getMeta('dates') ?? [];

        $dates = collect($dates)->map(function ($date) {
            return trim($date);
        })->toArray();

        $data = [];
        foreach ($allDates as $date) {
            $isApproved = in_array($date, $dates) ? true : false;
            $data[] = [
                'date' => \Cal::date($date),
                'is_approved' => $isApproved,
                'code' => $isApproved ? 'L' : 'LWP',
                'label' => $isApproved ? trans('employee.leave.leave') : trans('employee.leave.leave_without_pay'),
            ];
        }

        return $data;
    }
}
