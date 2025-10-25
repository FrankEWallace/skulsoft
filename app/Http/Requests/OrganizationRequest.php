<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganizationRequest extends FormRequest
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
        $uuid = $this->route('organization');

        return [
            'name' => ['required', 'max:100', 'min:3', Rule::unique('organizations')->ignore($uuid)],
            'code' => ['required', 'max:50', 'min:1', 'string', Rule::unique('organizations')->ignore($uuid)],
            'email' => ['nullable', 'email'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
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
            'name' => __('organization.props.name'),
            'code' => __('organization.props.code'),
        ];
    }
}
