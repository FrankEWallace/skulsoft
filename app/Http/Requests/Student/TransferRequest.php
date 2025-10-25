<?php

namespace App\Http\Requests\Student;

use App\Enums\OptionType;
use App\Models\Option;
use App\Models\Student\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class TransferRequest extends FormRequest
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
            'student' => 'required|uuid',
            'date' => 'required|date_format:Y-m-d',
            'reason' => 'required|uuid',
            'remarks' => 'nullable|string|max:255',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('transfer');

            $reason = Option::query()
                ->byTeam()
                ->where('type', OptionType::STUDENT_TRANSFER_REASON->value)
                ->whereUuid($this->reason)
                ->getOrFail(trans('student.transfer_reason.transfer_reason'), 'reason');

            $student = Student::query()
                ->byPeriod()
                ->summary()
                ->filterAccessible()
                ->where('students.uuid', $this->student)
                ->getOrFail(trans('student.student'), 'student');

            if ($student->joining_date > $this->date) {
                throw ValidationException::withMessages(['date' => trans('student.transfer.date_before_joining', ['attribute' => \Cal::date($student->joining_date)->formatted])]);
            }

            if ($student->start_date->value > $this->date) {
                throw ValidationException::withMessages(['date' => trans('student.transfer.date_before_promotion', ['attribute' => $student->start_date->formatted])]);
            }

            if ($this->method() == 'POST' && $student->leaving_date) {
                throw ValidationException::withMessages(['student' => trans('student.transfer.already_transferred')]);
            }

            $fee = $student->getFeeSummary();

            $total = Arr::get($fee, 'total_fee');
            $paid = Arr::get($fee, 'paid_fee');
            $balance = Arr::get($fee, 'balance_fee');

            // if ($balance->value > 0) {
            //     throw ValidationException::withMessages(['message' => trans('student.transfer_request.fee_due', ['total' => $total->formatted, 'paid' => $paid->formatted])]);
            // }

            $promotedStudent = Student::query()
                ->whereAdmissionId($student->admission_id)
                ->where('id', '!=', $student->id)
                ->where('start_date', '>', $student->start_date->value)
                ->whereNull('cancelled_at')
                ->exists();

            if ($promotedStudent) {
                throw ValidationException::withMessages(['student' => trans('student.transfer.already_promoted')]);
            }

            $this->merge([
                'student' => $student,
                'reason_id' => $reason->id,
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
            'student' => trans('student.student'),
            'reason' => trans('student.transfer.props.reason'),
            'date' => trans('student.transfer.props.date'),
            'remarks' => trans('student.transfer.props.remarks'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
        ];
    }
}
