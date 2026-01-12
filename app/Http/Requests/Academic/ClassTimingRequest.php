<?php

namespace App\Http\Requests\Academic;

use App\Helpers\CalHelper;
use App\Domain\Academic\Models\ClassTiming;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class ClassTimingRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:3', 'max:50'],
            'sessions' => 'array',
            'sessions.*.is_break' => 'boolean',
            'sessions.*.name' => ['required', 'string', 'min:1', 'max:50', 'distinct'],
            'sessions.*.start_time' => 'required|date_format:H:i:s',
            'sessions.*.end_time' => 'required|date_format:H:i:s',
            'description' => 'nullable|string|max:1000',
        ];

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('class_timing');

            $existingRecords = ClassTiming::query()
                ->byPeriod()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereName($this->name)
                ->exists();

            if ($existingRecords) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => trans('academic.class_timing.class_timing')]));
            }

            $sessions = [];
            $previousSessionEndTime = null;
            foreach ($this->sessions as $index => $session) {
                $name = Arr::get($session, 'name');
                $isBreak = (bool) Arr::get($session, 'is_break');
                $startTime = Arr::get($session, 'start_time');
                $endTime = Arr::get($session, 'end_time');

                if ($startTime >= $endTime) {
                    $validator->errors()->add("sessions.{$index}.start_time", __('academic.class_timing.start_time_should_less_than_end_time'));
                }

                if ($previousSessionEndTime && $startTime < $previousSessionEndTime) {
                    $validator->errors()->add("sessions.{$index}.start_time", __('academic.class_timing.start_time_should_greater_than_previous_session_end_time'));
                }

                $sessions[] = [
                    'name' => $name,
                    'is_break' => $isBreak,
                    'start_time' => CalHelper::storeDateTime($startTime)->toTimeString(),
                    'end_time' => CalHelper::storeDateTime($endTime)->toTimeString(),
                ];

                $previousSessionEndTime = $endTime;
            }

            $this->merge(['sessions' => $sessions]);
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
            'name' => __('academic.class_timing.props.name'),
            'description' => __('academic.class_timing.props.description'),
            'sessions' => __('academic.class_timing.session'),
            'sessions.*.name' => __('academic.class_timing.props.session'),
            'sessions.*.is_break' => __('academic.class_timing.props.is_break'),
            'sessions.*.start_time' => __('academic.class_timing.props.start_time'),
            'sessions.*.end_time' => __('academic.class_timing.props.end_time'),
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
