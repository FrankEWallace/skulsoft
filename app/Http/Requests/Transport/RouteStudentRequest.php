<?php

namespace App\Http\Requests\Transport;

use App\Models\Transport\Stoppage;
use Illuminate\Foundation\Http\FormRequest;

class RouteStudentRequest extends FormRequest
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
            'stoppage' => 'required|uuid',
            'students' => 'required|array|min:1',
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

            $stoppage = Stoppage::query()
                ->byPeriod()
                ->whereUuid($this->stoppage)
                ->getOrFail(__('transport.stoppage.stoppage'), 'stoppage');

            $this->merge([
                'stoppage_id' => $stoppage->id,
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
            'stoppage' => __('transport.stoppage.stoppage'),
            'students' => __('student.student'),
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
