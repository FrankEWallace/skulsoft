<?php

namespace App\Http\Requests\Employee\Attendance;

use App\Concerns\SubordinateAccess;
use App\Helpers\CalHelper;
use App\Models\Employee\Attendance\Timesheet;
use App\Models\Employee\Employee;
use App\Models\Employee\WorkShift as EmployeeWorkShift;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class TimesheetRequest extends FormRequest
{
    use SubordinateAccess;

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
        $rules = [
            'employee' => 'required',
            'date' => 'required|date',
            'in_at' => 'required|date_format:H:i:s',
            'out_at' => 'nullable|date_format:H:i:s',
            'remarks' => 'nullable|min:2|max:1000',
        ];

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('timesheet');

            $employee = Employee::query()
                ->summary()
                ->filterAccessible()
                ->where('employees.uuid', $this->employee)
                ->getOrFail(trans('employee.employee'), 'employee');

            $this->validateEmployeeJoiningDate($employee, $this->date, trans('employee.employee'), 'employee');

            $this->validateEmployeeLeavingDate($employee, $this->date, trans('employee.employee'), 'employee');

            $employeeWorkShift = EmployeeWorkShift::query()
                ->select('work_shifts.name', 'work_shifts.code', 'work_shifts.records', 'employee_work_shifts.start_date', 'employee_work_shifts.end_date', 'employee_work_shifts.employee_id', 'employee_work_shifts.work_shift_id')
                ->join('work_shifts', function ($join) {
                    $join->on('employee_work_shifts.work_shift_id', '=', 'work_shifts.id');
                })
                ->whereEmployeeId($employee->id)
                ->where('start_date', '<=', $this->date)
                ->where('end_date', '>=', $this->date)
                ->first();

            $day = strtolower(Carbon::parse($this->date)->format('l'));

            $workShiftRecord = [];
            if ($employeeWorkShift) {
                $workShiftRecords = json_decode($employeeWorkShift->records ?? '', true);

                $workShiftRecord = collect($workShiftRecords)->firstWhere('day', $day);
            }

            $inAt = CalHelper::storeDateTime($this->getInDateTime($workShiftRecord))->toDateTimeString();
            $outAt = CalHelper::storeDateTime($this->getOutDateTime($workShiftRecord, $inAt))?->toDateTimeString();

            if ($outAt && $inAt > $outAt) {
                $validator->errors()->add('in_at', trans('employee.attendance.timesheet.start_time_should_less_than_end_time'));
            }

            $existingTimesheets = TimeSheet::query()
                ->when($uuid, function ($query) use ($uuid) {
                    $query->where('uuid', '!=', $uuid);
                })
                ->whereEmployeeId($employee->id)
                ->where('date', $this->date)
                ->get();

            if ($existingTimesheets->where('out_at', null)->count()) {
                $validator->errors()->add('in_at', trans('employee.attendance.timesheet.could_not_perform_if_empty_out_at'));
            }

            $this->merge([
                'employee_id' => $employee->id,
                'work_shift_id' => $employeeWorkShift?->work_shift_id,
                'in_at' => $inAt,
                'out_at' => $outAt,
                'is_overnight' => (bool) Arr::get($workShiftRecord, 'is_overnight', false),
                'is_holiday' => (bool) Arr::get($workShiftRecord, 'is_holiday', false),
            ]);

            if ($outAt) {
                $overlappingTimesheet = Timesheet::query()
                    ->when($uuid, function ($query) use ($uuid) {
                        $query->where('uuid', '!=', $uuid);
                    })
                    ->whereEmployeeId($employee->id)
                    ->where('date', $this->date)
                    ->where(function ($q) use ($inAt, $outAt) {
                        $q->where(function ($q) use ($inAt) {
                            $q->where('in_at', '<=', $inAt)->where('out_at', '>=', $inAt);
                        })->orWhere(function ($q) use ($outAt) {
                            $q->where('in_at', '<=', $outAt)->where('out_at', '>=', $outAt);
                        })->orWhere(function ($q) use ($inAt, $outAt) {
                            $q->where('in_at', '>=', $inAt)->where('out_at', '<=', $outAt);
                        });
                    })->exists();
            } else {
                $overlappingTimesheet = Timesheet::query()
                    ->when($uuid, function ($query) use ($uuid) {
                        $query->where('uuid', '!=', $uuid);
                    })
                    ->whereEmployeeId($employee->id)
                    ->where('date', $this->date)
                    ->where('in_at', '<=', $inAt)->where('out_at', '>=', $inAt)
                    ->exists();
            }

            if ($overlappingTimesheet) {
                $validator->errors()->add('in_at', trans('employee.attendance.timesheet.range_exists', ['start' => CalHelper::showTime($inAt), 'end' => CalHelper::showTime($outAt)]));
            }
        });
    }

    private function getInDateTime(array $workShiftRecord = []): ?string
    {
        $time = $this->in_at;

        if (empty($workShiftRecord)) {
            return Carbon::parse($this->date.' '.$time)->toDateTimeString();
        }

        $isHoliday = Arr::get($workShiftRecord, 'is_holiday', false);
        $isOvernight = Arr::get($workShiftRecord, 'is_overnight', false);

        $datetime = Carbon::parse($this->date.' '.$time);

        if ($isHoliday) {
            return $datetime->toDateTimeString();
        }

        if (! $isOvernight) {
            return $datetime->toDateTimeString();
        }

        if ($datetime->toDateTimeString() > Carbon::parse($this->date.' 09:00:00')->toDateTimeString()) {
            return $datetime->toDateTimeString();
        }

        return $datetime->addDay(1)->toDateTimeString();
    }

    private function getOutDateTime(array $workShiftRecord, string $inAt): ?string
    {
        $time = $this->out_at;

        if (empty($time)) {
            return null;
        }

        if (empty($workShiftRecord)) {
            return Carbon::parse($this->date.' '.$time)->toDateTimeString();
        }

        $isHoliday = Arr::get($workShiftRecord, 'is_holiday', false);
        $isOvernight = Arr::get($workShiftRecord, 'is_overnight', false);

        $inDatetime = Carbon::parse($inAt);

        if ($isOvernight) {
            // suggestion: if out time is of next day then add 1 day to in time else use same date
            $datetime = Carbon::parse($inDatetime->toDateString().' '.$time);
        } else {
            $datetime = Carbon::parse($this->date.' '.$time);
        }

        if ($isHoliday) {
            return $datetime->toDateTimeString();
        }

        if (! $isOvernight) {
            return $datetime->toDateTimeString();
        }

        if ($inDatetime->toTimeString() < $datetime->toTimeString()) {
            return $datetime->toDateTimeString();
        }

        return $datetime->addDay(1)->toDateTimeString();
    }

    /**
     * Translate fields with user friendly name.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'employee' => __('employee.employee'),
            'date' => __('employee.attendance.timesheet.props.date'),
            'in_at' => __('employee.attendance.timesheet.props.in_at'),
            'out_at' => __('employee.attendance.timesheet.props.out_at'),
            'remarks' => __('employee.attendance.timesheet.props.remarks'),
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
