<?php

namespace App\Http\Requests\Student;

use App\Helpers\CalHelper;
use App\Models\Student\Student;
use App\Models\Student\Timesheet;
use Illuminate\Foundation\Http\FormRequest;

class TimesheetRequest extends FormRequest
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
        $rules = [
            'student' => 'required',
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

            $student = Student::query()
                ->summary()
                ->filterAccessible()
                ->where('students.uuid', $this->student)
                ->getOrFail(trans('student.student'), 'student');

            $inAt = $this->in_at ? CalHelper::storeDateTime($this->in_at)->toTimeString() : null;
            $outAt = $this->out_at ? CalHelper::storeDateTime($this->out_at)?->toTimeString() : null;

            if ($outAt && $inAt > $outAt) {
                $validator->errors()->add('in_at', trans('student.timesheet.start_time_should_less_than_end_time'));
            }

            $existingTimesheets = TimeSheet::query()
                ->when($uuid, function ($query) use ($uuid) {
                    $query->where('uuid', '!=', $uuid);
                })
                ->whereStudentId($student->id)
                ->where('date', $this->date)
                ->get();

            if ($existingTimesheets->count()) {
                $validator->errors()->add('in_at', trans('global.duplicate', ['attribute' => trans('student.timesheet.timesheet')]));
            }

            $this->merge([
                'student_id' => $student->id,
                'in_at' => $inAt,
                'out_at' => $outAt,
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
            'student' => __('student.student'),
            'date' => __('student.timesheet.props.date'),
            'in_at' => __('student.timesheet.props.in_at'),
            'out_at' => __('student.timesheet.props.out_at'),
            'remarks' => __('student.timesheet.props.remarks'),
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
