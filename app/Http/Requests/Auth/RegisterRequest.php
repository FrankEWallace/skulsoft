<?php

namespace App\Http\Requests\Auth;

use App\Rules\StrongPassword;
use App\Rules\Username;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'name' => 'required|min:2|max:50',
            'email' => 'required|email|max:50|unique:users',
            'username' => ['required', 'unique:users', new Username],
            'password' => ['required', 'same:password_confirmation', new StrongPassword],
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
            'name' => __('auth.register.props.name'),
            'email' => __('auth.register.props.email'),
            'username' => __('auth.register.props.username'),
            'password' => __('auth.register.props.password'),
            'password_confirmation' => __('auth.register.props.password_confirmation'),
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
