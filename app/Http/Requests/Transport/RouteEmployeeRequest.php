<?php

namespace App\Http\Requests\Transport;

use App\Models\Transport\Stoppage;
use Illuminate\Foundation\Http\FormRequest;

class RouteEmployeeRequest extends FormRequest
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
            'stoppage' => 'nullable|uuid',
            'title' => 'nullable|min:3|max:100',
            'publish_contact_number' => 'boolean',
            'employees' => 'required|array|min:1',
        ];

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('route');

            $stoppage = $this->stoppage ? Stoppage::query()
                ->byPeriod()
                ->whereUuid($this->stoppage)
                ->getOrFail(__('transport.stoppage.stoppage'), 'stoppage') : null;

            $this->merge([
                'stoppage_id' => $stoppage?->id,
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
            'title' => __('transport.route.props.title'),
            'stoppage' => __('transport.stoppage.stoppage'),
            'employees' => __('employee.employee'),
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
