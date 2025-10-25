<?php

namespace App\Http\Requests\Employee\Leave;

use App\Enums\Employee\Leave\RequestStatus as LeaveRequestStatus;
use App\Helpers\CalHelper;
use App\Models\Employee\Leave\Allocation as LeaveAllocation;
use App\Models\Employee\Leave\Request as LeaveRequest;
use App\Models\Employee\Payroll\Payroll;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class RequestStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'status' => ['required', new Enum(LeaveRequestStatus::class)],
            'comment' => 'required|min:10|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('leave_request');

            $leaveRequest = LeaveRequest::query()
                ->whereUuid($uuid)
                ->with(['model' => fn ($q) => $q->summary()])
                ->getOrFail(trans('employee.leave.request.request'), 'message');

            if ($leaveRequest->status != LeaveRequestStatus::REQUESTED) {
                $validator->errors()->add('status', trans('general.errors.invalid_input'));

                return;
            }

            if ($leaveRequest->start_date->value == $leaveRequest->end_date->value && $this->status == LeaveRequestStatus::PARTIALLY_APPROVED->value) {
                $validator->errors()->add('status', trans('general.errors.invalid_input'));

                return;
            }

            $payrollGenerated = Payroll::query()
                ->whereEmployeeId($leaveRequest->employee_id)
                ->betweenPeriod($leaveRequest->start_date->value, $leaveRequest->end_date->value)
                ->exists();

            if ($payrollGenerated) {
                throw ValidationException::withMessages(['message' => trans('employee.leave.request.could_not_perform_if_payroll_generated')]);
            }

            if ($leaveRequest->is_half_day && $this->status == LeaveRequestStatus::PARTIALLY_APPROVED->value) {
                $validator->errors()->add('status', trans('general.errors.invalid_input'));

                return;
            }

            $dates = [];
            if ($this->status == LeaveRequestStatus::PARTIALLY_APPROVED->value) {
                // if (empty($this->dates)) {
                //     $validator->errors()->add('dates', trans('validation.required', ['attribute' => trans('general.date')]));

                //     return;
                // }

                $dates = explode(',', $this->dates);

                $dates = collect($dates)->filter(function ($date) {
                    return ! empty($date);
                })->toArray();

                foreach ($dates as $date) {
                    if (! CalHelper::validateDate($date)) {
                        $validator->errors()->add('dates', trans('validation.date', ['attribute' => trans('general.date')]));

                        return;
                    }

                    $date = Carbon::parse($date);

                    if ($date->lessThan($leaveRequest->start_date->value) || $date->greaterThan($leaveRequest->end_date->value)) {
                        $validator->errors()->add('dates', trans('employee.leave.request.invalid_date'));

                        return;
                    }
                }
            }

            $duration = $this->status != LeaveRequestStatus::PARTIALLY_APPROVED->value ? CalHelper::dateDiff($leaveRequest->start_date->value, $leaveRequest->end_date->value) : count($dates);

            $requestWithExhaustedCredit = config('config.employee.allow_employee_request_leave_with_exhausted_credit');

            $query = LeaveAllocation::query()
                ->with('records')
                ->whereEmployeeId($leaveRequest->model_id)
                ->where('start_date', '<=', $leaveRequest->start_date->value)
                ->where('end_date', '>=', $leaveRequest->end_date->value);

            if ($requestWithExhaustedCredit) {
                $leaveAllocation = $query->first();
            } else {
                $leaveAllocation = $query->getOrFail(trans('employee.leave.allocation.allocation'));
            }

            if ($leaveAllocation) {
                if ($requestWithExhaustedCredit) {
                    $leaveAllocationRecord = $leaveAllocation->records->where('leave_type_id', $leaveRequest->leave_type_id)->first();
                } else {
                    $leaveAllocationRecord = $leaveAllocation->records->where('leave_type_id', $leaveRequest->leave_type_id)->hasOrFail(trans('employee.leave.type.no_allocation_found'));
                }
            } else {
                $leaveAllocationRecord = null;
            }

            $balance = 0;

            if ($leaveAllocationRecord) {
                $balance = $leaveAllocationRecord->allotted - $leaveAllocationRecord->used;
            }

            if (! $requestWithExhaustedCredit) {
                if ($this->status == LeaveRequestStatus::APPROVED->value || $this->status == LeaveRequestStatus::PARTIALLY_APPROVED->value) {
                    if (($leaveRequest->status != LeaveRequestStatus::APPROVED || $leaveRequest->status != LeaveRequestStatus::PARTIALLY_APPROVED) && $balance < $duration) {
                        throw ValidationException::withMessages(['message' => trans('employee.leave.type.balance_exhausted', ['balance' => $balance, 'duration' => $duration])]);
                    }
                }
            }

            if ($balance <= 0 && $this->status == LeaveRequestStatus::PARTIALLY_APPROVED->value) {
                throw ValidationException::withMessages(['message' => trans('employee.leave.type.balance_exhausted', ['balance' => $balance, 'duration' => $duration])]);
            }

            $this->merge([
                'leave_request' => $leaveRequest,
                'leave_allocation_id' => $leaveAllocation?->id,
                'duration' => $duration,
                'balance' => $balance,
                'dates' => $dates,
            ]);
        });
    }

    /**
     * Translate fields with user friendly name.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'status' => __('employee.leave.request.props.status'),
            'comment' => __('employee.leave.request.props.comment'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }
}
