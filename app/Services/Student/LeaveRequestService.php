<?php

namespace App\Services\Student;

use App\Enums\OptionType;
use App\Enums\Student\LeaveRequestStatus;
use App\Http\Resources\OptionResource;
use App\Http\Resources\Student\StudentSummaryResource;
use App\Models\Option;
use App\Models\Student\LeaveRequest;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LeaveRequestService
{
    public function preRequisite(Request $request): array
    {
        $students = [];
        if (auth()->user()->is_student_or_guardian) {
            $students = StudentSummaryResource::collection(Student::query()
                ->byPeriod()
                ->summary()
                ->filterForStudentAndGuardian()
                ->get());
        }

        $categories = OptionResource::collection(Option::query()
            ->byTeam()
            ->where('type', OptionType::STUDENT_LEAVE_CATEGORY->value)
            ->get());

        return compact('categories', 'students');
    }

    public function create(Request $request): void
    {
        \DB::beginTransaction();

        $leaveRequest = LeaveRequest::forceCreate($this->formatParams($request));

        $leaveRequest->addMedia($request);

        \DB::commit();
    }

    private function formatParams(Request $request, ?LeaveRequest $leaveRequest = null): array
    {
        $formatted = [
            'model_type' => 'Student',
            'model_id' => $request->student_id,
            'category_id' => $request->category_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
        ];

        if (! $leaveRequest) {
            $formatted['status'] = LeaveRequestStatus::REQUESTED;
            $formatted['request_user_id'] = auth()->id();
        }

        return $formatted;
    }

    private function isEditable(Request $request, LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->start_date < today()->toDateString()) {
            throw ValidationException::withMessages(['message' => trans('student.leave_request.could_not_perform_for_past_date')]);
        }

        if (! in_array($leaveRequest->status, [LeaveRequestStatus::REQUESTED])) {
            throw ValidationException::withMessages(['message' => trans('student.leave_request.could_not_perform_if_status_updated')]);
        }

        if (! $leaveRequest->is_editable) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }
    }

    public function update(Request $request, LeaveRequest $leaveRequest): void
    {
        $this->isEditable($request, $leaveRequest);

        \DB::beginTransaction();

        $leaveRequest->forceFill($this->formatParams($request, $leaveRequest))->save();

        $leaveRequest->updateMedia($request);

        \DB::commit();
    }

    public function deletable(Request $request, LeaveRequest $leaveRequest): void
    {
        $this->isEditable($request, $leaveRequest);
    }

    public function delete(LeaveRequest $leaveRequest): void
    {
        \DB::beginTransaction();

        $leaveRequest->delete();

        \DB::commit();
    }
}
