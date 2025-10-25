<?php

namespace App\Http\Requests\Contact;

use App\Models\Team\Role;
use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends FormRequest
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
            'roles' => 'required|array|min:1',
            'force_change_password' => 'boolean',
            'password' => ['required_if:force_change_password,true', 'same:password_confirmation', new StrongPassword],
            'password_confirmation' => ['required_if:force_change_password,true'],
        ];

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $allowedRoles = Role::selectOption();

            if (array_diff($this->roles, $allowedRoles->pluck('uuid')->all())) {
                $validator->errors()->add('roles', trans('general.errors.invalid_input'));
            }

            if ($this->force_change_password && ! auth()->user()->canAny(['user:force-change-password', 'user:edit'])) {
                $validator->errors()->add('message', trans('user.errors.permission_denied'));
            }

            $this->merge(['role_ids' => $allowedRoles->whereIn('uuid', $this->roles)->pluck('id')->all()]);
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
            'role' => __('contact.login.props.role'),
            'password' => __('contact.login.props.password'),
            'password_confirmation' => __('contact.login.props.password_confirmation'),
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
            'password.required_if' => trans('validation.required', ['attribute' => __('contact.login.props.password')]),
            'password_confirmation.required_if' => trans('validation.required', ['attribute' => __('contact.login.props.password_confirmation')]),
        ];
    }
}
