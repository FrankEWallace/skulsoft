<?php

namespace App\Http\Requests\Calendar;

use App\Enums\Employee\AudienceType as EmployeeAudienceType;
use App\Enums\OptionType;
use App\Enums\Student\AudienceType as StudentAudienceType;
use App\Domain\Academic\Models\Period;
use App\Domain\Academic\Models\Session;
use App\Models\Calendar\Event;
use App\Models\Employee\Employee;
use App\Models\Option;
use App\Support\HasAudience;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Enum;

class EventRequest extends FormRequest
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
            'incharge' => 'nullable|uuid',
            'is_public' => 'boolean',
            'for_alumni' => 'boolean',
            'sessions' => 'array',
            'periods' => 'array',
            'student_audience_type' => [new Enum(StudentAudienceType::class)],
            'employee_audience_type' => [new Enum(EmployeeAudienceType::class)],
            'student_audiences' => 'array|required_if:student_audience_type,division_wise,course_wise,batch_wise',
            'employee_audiences' => 'array|required_if:employee_audience_type,department_wise,designation_wise',
            'description' => 'nullable|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new Event)->getModelName();

            $eventUuid = $this->route('event');

            $type = $this->type ? Option::query()
                ->byTeam()
                ->whereType(OptionType::EVENT_TYPE->value)
                ->whereUuid($this->type)
                ->getOrFail(__('calendar.event.type.type'), 'type') : null;

            $incharge = $this->incharge ? Employee::query()
                ->byTeam()
                ->where('uuid', $this->incharge)
                ->getOrFail(trans('employee.employee'), 'employee') : null;

            // check for duplicate event

            $data = $this->validateInput($this->all());

            $periods = [];
            $sessions = [];

            if (! $this->is_public && $this->for_alumni) {
                $periods = Period::query()
                    ->byTeam()
                    ->whereIn('uuid', $this->periods)
                    ->get()?->pluck('uuid') ?? [];

                $sessions = Session::query()
                    ->byTeam()
                    ->whereIn('id', $this->sessions)
                    ->get()?->pluck('uuid') ?? [];
            }

            $this->merge([
                'type_id' => $type?->id,
                'incharge_id' => $incharge?->id,
                'student_audience_type' => Arr::get($data, 'studentAudienceType'),
                'employee_audience_type' => Arr::get($data, 'employeeAudienceType'),
                'student_audiences' => Arr::get($data, 'studentAudiences'),
                'employee_audiences' => Arr::get($data, 'employeeAudiences'),
                'periods' => $periods,
                'sessions' => $sessions,
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
            'type' => __('calendar.event.type.type'),
            'title' => __('calendar.event.props.title'),
            'venue' => __('calendar.event.props.venue'),
            'start_date' => __('calendar.event.props.start_date'),
            'start_time' => __('calendar.event.props.start_time'),
            'end_date' => __('calendar.event.props.end_date'),
            'end_time' => __('calendar.event.props.end_time'),
            'incharge' => __('employee.incharge.incharge'),
            'student_audience_type' => __('calendar.event.props.audience'),
            'employee_audience_type' => __('calendar.event.props.audience'),
            'student_audiences' => __('calendar.event.props.audience'),
            'employee_audiences' => __('calendar.event.props.audience'),
            'description' => __('calendar.event.props.description'),
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
            'student_audiences.required_if' => __('validation.required', ['attribute' => trans('calendar.event.props.audience')]),
            'employee_audiences.required_if' => __('validation.required', ['attribute' => trans('calendar.event.props.audience')]),
        ];
    }
}
