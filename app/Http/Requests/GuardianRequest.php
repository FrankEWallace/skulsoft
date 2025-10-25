<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Rules\AlphaSpace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class GuardianRequest extends FormRequest
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
            'first_name' => ['required', 'min:2', 'max:100', new AlphaSpace],
            'last_name' => ['nullable', 'max:100', new AlphaSpace],
            'gender' => ['required', new Enum(Gender::class)],
            'birth_date' => ['required', 'date_format:Y-m-d'],
            'contact_number' => ['required', 'min:4', 'max:20'],
        ];

        if (config('config.contact.enable_middle_name_field')) {
            $rules['middle_name'] = ['nullable', 'max:100', new AlphaSpace];
        }

        if (config('config.contact.enable_third_name_field')) {
            $rules['third_name'] = ['nullable', 'max:100', new AlphaSpace];
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('contact');

            $this->merge([
                //
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
            'first_name' => __('contact.props.first_name'),
            'last_name' => __('contact.props.last_name'),
            'middle_name' => __('contact.props.middle_name'),
            'third_name' => __('contact.props.third_name'),
            'gender' => __('contact.props.gender'),
            'birth_date' => __('contact.props.birth_date'),
            'contact_number' => __('contact.props.contact_number'),
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
