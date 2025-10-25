<?php

namespace App\Http\Requests\Config;

use Illuminate\Foundation\Http\FormRequest;

class AssetRequest extends FormRequest
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
        return [];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $allowedTypes = [
                'guest_background',
                'guest_full_page_background',
                'logo',
                'logo_light',
                'favicon',
                'icon',
                'icon_light',
            ];

            if (! in_array($this->query('type'), $allowedTypes)) {
                $validator->errors()->add('message', trans('general.errors.invalid_input'));
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
        return [];
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
