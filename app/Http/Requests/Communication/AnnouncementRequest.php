<?php

namespace App\Http\Requests\Communication;

use App\Enums\Employee\AudienceType as EmployeeAudienceType;
use App\Enums\OptionType;
use App\Enums\Student\AudienceType as StudentAudienceType;
use App\Models\Communication\Announcement;
use App\Models\Option;
use App\Support\HasAudience;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Enum;

class AnnouncementRequest extends FormRequest
{
    use HasAudience;

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
            'title' => 'required|max:255',
            'type' => 'required|uuid',
            'is_public' => 'boolean',
            'student_audience_type' => [new Enum(StudentAudienceType::class)],
            'employee_audience_type' => [new Enum(EmployeeAudienceType::class)],
            'student_audiences' => 'array|required_if:student_audience_type,division_wise,course_wise,batch_wise',
            'employee_audiences' => 'array|required_if:employee_audience_type,department_wise,designation_wise',
            'description' => 'nullable|min:2|max:10000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new Announcement)->getModelName();

            $announcementUuid = $this->route('announcement');

            $type = $this->type ? Option::query()
                ->byTeam()
                ->whereType(OptionType::ANNOUNCEMENT_TYPE->value)
                ->whereUuid($this->type)
                ->getOrFail(__('communication.announcement.type.type'), 'type') : null;

            // check for duplicate announcement

            $data = $this->validateInput($this->all());

            $this->merge([
                'type_id' => $type?->id,
                'student_audience_type' => Arr::get($data, 'studentAudienceType'),
                'employee_audience_type' => Arr::get($data, 'employeeAudienceType'),
                'student_audiences' => Arr::get($data, 'studentAudiences'),
                'employee_audiences' => Arr::get($data, 'employeeAudiences'),
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
            'title' => __('communication.announcement.props.title'),
            'is_public' => __('communication.announcement.props.is_public'),
            'type' => __('communication.announcement.type.type'),
            'student_audience_type' => __('communication.announcement.props.audience'),
            'employee_audience_type' => __('communication.announcement.props.audience'),
            'student_audiences' => __('communication.announcement.props.audience'),
            'employee_audiences' => __('communication.announcement.props.audience'),
            'description' => __('communication.announcement.props.description'),
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
            'student_audiences.required_if' => __('validation.required', ['attribute' => trans('communication.announcement.props.audience')]),
            'employee_audiences.required_if' => __('validation.required', ['attribute' => trans('communication.announcement.props.audience')]),
        ];
    }
}
