<?php

namespace App\Http\Requests\Calendar;

use App\Helpers\CalHelper;
use App\Models\Calendar\Holiday;
use Illuminate\Foundation\Http\FormRequest;

class HolidayRequest extends FormRequest
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
            'type' => 'required|in:range,dates,weekend',
            'name' => 'required|min:2|max:100',
            'start_date' => 'required_if:type,range|date_format:Y-m-d',
            'end_date' => 'required_if:type,range|date_format:Y-m-d|after_or_equal:start_date',
            'dates' => 'required_if:type,dates',
            'days' => 'required_if:type,weekend',
            'description' => 'nullable|min:2|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('holiday.uuid');

            if ($this->type == 'dates') {
                $dates = explode(',', $this->dates);

                foreach ($dates as $date) {
                    if (! CalHelper::validateDate($date)) {
                        $validator->errors()->add('dates', trans('validation.date', ['attribute' => trans('calendar.holiday.props.dates')]));

                        return;
                    }

                    $date = \Cal::date($date);

                    $overlappingHoliday = Holiday::query()
                        ->byPeriod()
                        ->where('start_date', '<=', $date->value)
                        ->where('end_date', '>=', $date->value)
                        ->count();

                    if ($overlappingHoliday) {
                        $validator->errors()->add('dates', trans('calendar.holiday.exists', ['attribute' => $date->formatted]));
                    }
                }

                $this->merge([
                    'dates' => $dates,
                ]);
            } elseif ($this->type == 'range') {
                $startDate = \Cal::date($this->start_date);
                $endDate = \Cal::date($this->end_date);

                $overlappingHoliday = Holiday::query()
                    ->byPeriod()
                    ->when($uuid, function ($q, $uuid) {
                        $q->where('uuid', '!=', $uuid);
                    })
                    ->betweenPeriod($startDate->value, $endDate->value)
                    ->count();

                if ($overlappingHoliday) {
                    $validator->errors()->add('message', trans('calendar.holiday.range_exists', ['start' => $startDate->formatted, 'end' => $endDate->formatted]));
                }
            } elseif ($this->type == 'weekend') {
            }
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
            'name' => __('calendar.holiday.props.name'),
            'type' => __('calendar.holiday.props.type'),
            'start_date' => __('calendar.holiday.props.start_date'),
            'end_date' => __('calendar.holiday.props.end_date'),
            'dates' => __('calendar.holiday.props.dates'),
            'days' => __('calendar.holiday.props.days'),
            'description' => __('calendar.holiday.props.description'),
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
