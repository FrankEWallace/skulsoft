<?php

namespace App\Http\Requests\Activity;

use App\Enums\Employee\AudienceType as EmployeeAudienceType;
use App\Enums\OptionType;
use App\Enums\Student\AudienceType as StudentAudienceType;
use App\Models\Activity\Trip;
use App\Models\Option;
use App\Support\HasAudience;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Enum;

class TripRequest extends FormRequest
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
            'type' => 'required|uuid',
            'title' => 'required|max:255',
            'venue' => 'required|max:255',
            'start_date' => 'required|date_format:Y-m-d',
            'start_time' => 'nullable|date_format:H:i:s',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'end_time' => 'nullable|date_format:H:i:s',
            'fee' => 'integer|min:0',
            'student_audience_type' => [new Enum(StudentAudienceType::class)],
            'employee_audience_type' => [new Enum(EmployeeAudienceType::class)],
            'student_audiences' => 'array|required_if:student_audience_type,division_wise,course_wise,batch_wise',
            'employee_audiences' => 'array|required_if:employee_audience_type,department_wise,designation_wise',
            'summary' => 'nullable|max:1000',
            'itinerary' => 'nullable|max:10000',
            'description' => 'nullable|max:10000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new Trip)->getModelName();

            $tripUuid = $this->route('trip');

            $type = $this->type ? Option::query()
                ->byTeam()
                ->whereType(OptionType::TRIP_TYPE->value)
                ->whereUuid($this->type)
                ->getOrFail(__('activity.trip.type.type'), 'type') : null;

            // check for duplicate event

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
            'type' => __('activity.trip.type.type'),
            'title' => __('activity.trip.props.title'),
            'venue' => __('activity.trip.props.venue'),
            'start_date' => __('activity.trip.props.start_date'),
            'start_time' => __('activity.trip.props.start_time'),
            'end_date' => __('activity.trip.props.end_date'),
            'end_time' => __('activity.trip.props.end_time'),
            'fee' => __('activity.trip.props.fee'),
            'student_audience_type' => __('activity.trip.props.audience'),
            'employee_audience_type' => __('activity.trip.props.audience'),
            'student_audiences' => __('activity.trip.props.audience'),
            'employee_audiences' => __('activity.trip.props.audience'),
            'summary' => __('activity.trip.props.summary'),
            'itinerary' => __('activity.trip.props.itinerary'),
            'description' => __('activity.trip.props.description'),
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
            'student_audiences.required_if' => __('validation.required', ['attribute' => trans('activity.trip.props.audience')]),
            'employee_audiences.required_if' => __('validation.required', ['attribute' => trans('activity.trip.props.audience')]),
        ];
    }
}
