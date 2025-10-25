<?php

namespace App\Http\Requests\Config;

use Illuminate\Foundation\Http\FormRequest;

class SMSTemplateRequest extends FormRequest
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
            'subject' => ['required', 'max:100', 'min:3'],
            'content' => 'required|max:200|min:2',
            'template_id' => 'nullable|max:100|min:2',
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
            'subject' => __('config.sms.template.props.subject'),
            'content' => __('config.sms.template.props.content'),
            'template_id' => __('config.sms.template.props.template_id'),
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
