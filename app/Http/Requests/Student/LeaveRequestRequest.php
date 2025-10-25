<?php

namespace App\Http\Requests\Student;

use App\Enums\OptionType;
use App\Helpers\CalHelper;
use App\Models\Media;
use App\Models\Option;
use App\Models\Student\LeaveRequest;
use App\Models\Student\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class LeaveRequestRequest extends FormRequest
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
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'category' => 'required|uuid',
            'reason' => 'required|max:500',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('leave_request');

            $mediaModel = (new LeaveRequest)->getModelName();

            $student = Student::query()
                ->byPeriod()
                ->summary()
                ->filterAccessible()
                ->where('students.uuid', $this->student)
                ->getOrFail(trans('student.student'), 'student');

            if ($student->joining_date > $this->start_date) {
                throw ValidationException::withMessages(['date' => trans('student.leave_request.date_before_joining', ['attribute' => \Cal::date($student->joining_date)->formatted])]);
            }

            if ($student->start_date->value > $this->start_date) {
                throw ValidationException::withMessages(['date' => trans('student.leave_request.date_before_promotion', ['attribute' => $student->start_date->formatted])]);
            }

            if ($student->user_id == auth()->id() && $student->leaving_date && $student->leaving_date < today()->toDateString()) {
                throw ValidationException::withMessages(['student' => trans('student.transfer_request.already_transferred')]);
            }

            $category = Option::query()
                ->byTeam()
                ->whereType(OptionType::STUDENT_LEAVE_CATEGORY->value)
                ->whereUuid($this->category)
                ->getOrFail(trans('student.leave_category.leave_category'), 'leave_category');

            $overlappingRequest = LeaveRequest::query()
                ->whereModelType('Student')
                ->whereModelId($student->id)
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->betweenPeriod($this->start_date, $this->end_date)
                ->count();

            if ($overlappingRequest) {
                $validator->errors()->add('message', trans('student.leave_request.range_exists', ['start' => CalHelper::showDate($this->start_date), 'end' => CalHelper::showDate($this->end_date)]));
            }

            if (auth()->user()->is_student_or_guardian) {
                $attachedMedia = Media::whereModelType($mediaModel)
                    ->whereToken($this->media_token)
                    // ->where('meta->hash', $this->media_hash)
                    ->where('meta->is_temp_deleted', false)
                    ->where(function ($q) use ($uuid) {
                        $q->whereStatus(0)
                            ->when($uuid, function ($q) {
                                $q->orWhere('status', 1);
                            });
                    })
                    ->exists();

                if (! $attachedMedia) {
                    throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('general.attachment')])]);
                }
            }

            $this->merge([
                'student_id' => $student->id,
                'category_id' => $category->id,
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
            'leave_category' => trans('student.leave_category.leave_category'),
            'start_date' => trans('student.leave_request.props.start_date'),
            'end_date' => trans('student.leave_request.props.end_date'),
            'reason' => trans('student.leave_request.props.reason'),
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
