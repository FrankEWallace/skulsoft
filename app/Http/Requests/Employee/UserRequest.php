<?php

namespace App\Http\Requests\Employee;

use App\Models\Team\Role;
use App\Rules\StrongPassword;
use App\Rules\Username;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:50', Rule::unique('users')],
            'username' => ['required', Rule::unique('users'), new Username],
            'password' => ['required', 'same:password_confirmation', new StrongPassword],
            'roles' => 'required|array|min:1',
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

            if (str_contains($this->url(), 'employee')) {
                $this->roles = $allowedRoles->whereNotIn('name', ['student'])->whereIn('uuid', $this->roles)->pluck('uuid')->all();
            }

            if (empty($this->roles)) {
                $validator->errors()->add('roles', trans('general.errors.invalid_input'));
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
            'email' => __('contact.login.props.email'),
            'username' => __('contact.login.props.username'),
            'password' => __('contact.login.props.password'),
            'password_confirmation' => __('contact.login.props.password_confirmation'),
            'role' => __('contact.login.props.role'),
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
