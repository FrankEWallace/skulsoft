<?php

namespace App\Http\Requests;

use App\Concerns\LocalStorage;
use App\Helpers\ListHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class UserPreferenceRequest extends FormRequest
{
    use LocalStorage;

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
            'timezone' => 'sometimes|required',
            'date_format' => 'sometimes|required',
            'time_format' => 'sometimes|required',
            'locale' => 'sometimes|required',
            'sidebar' => 'sometimes|required|in:mini,pinned',
            'display' => 'sometimes|required|in:dark,light',
        ];
    }

    /**
     * Translate fields with user friendly name.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'timezone' => __('user.preference.props.timezone'),
            'date_format' => __('user.preference.props.date_format'),
            'time_format' => __('user.preference.props.time_format'),
            'locale' => __('user.preference.props.locale'),
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

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            if ($this->timezone && ! ListHelper::getListByKey('timezones', 'value', $this->timezone)) {
                $validator->errors()->add('timezone', trans('validation.exists', ['attribute' => trans('config.system.props.timezone')]));
            }

            if ($this->date_format && ! ListHelper::getListById('date_formats', $this->date_format)) {
                $validator->errors()->add('date_format', trans('validation.exists', ['attribute' => trans('config.system.props.date_format')]));
            }

            if ($this->time_format && ! ListHelper::getListById('time_formats', $this->time_format)) {
                $validator->errors()->add('time_format', trans('validation.exists', ['attribute' => trans('config.system.props.time_format')]));
            }

            if ($this->locale && ! in_array($this->locale, Arr::pluck($this->getKey('locales'), 'code'))) {
                $validator->errors()->add('locale', trans('validation.exists', ['attribute' => trans('config.locale.locale')]));
            }
        });
    }
}
