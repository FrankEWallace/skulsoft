<?php

namespace App\Http\Requests\Student;

use App\Enums\ServiceRequestType;
use App\Enums\ServiceType;
use App\Models\Transport\Stoppage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class ServiceAllocationRequest extends FormRequest
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
            'students' => ['required', 'array'],
            'type' => ['required'],
            'request_type' => ['required'],
            'transport_stoppage' => ['nullable', 'uuid'],
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            if (! in_array($this->type, explode(',', config('config.student.services')))) {
                throw ValidationException::withMessages(['type' => trans('general.errors.invalid_input')]);
            }

            $transportStoppage = null;
            if ($this->type == ServiceType::TRANSPORT->value && $this->request_type == ServiceRequestType::OPT_IN->value) {
                if (! $this->transport_stoppage) {
                    throw ValidationException::withMessages(['transport_stoppage' => trans('validation.required', ['attribute' => trans('transport.stoppage.stoppage')])]);
                } else {
                    $transportStoppage = Stoppage::query()
                        ->byPeriod()
                        ->where('uuid', $this->transport_stoppage)
                        ->getOrFail(trans('transport.stoppage.stoppage'), 'transport_stoppage');
                }
            }

            $this->merge([
                'transport_stoppage_id' => $transportStoppage?->id,
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
            'students' => __('student.student'),
            'type' => __('student.service_request.props.type'),
            'request_type' => __('student.service_request.props.request_type'),
            'transport_stoppage' => __('transport.stoppage.stoppage'),
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
            //
        ];
    }
}
