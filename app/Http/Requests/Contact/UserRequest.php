<?php

namespace App\Http\Requests\Contact;

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
            'roles' => 'array',
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

            if (str_contains($this->url(), 'student')) {
                $this->roles = [$allowedRoles->firstWhere('name', 'student')?->uuid];
            } elseif (str_contains($this->url(), 'guardian')) {
                $this->roles = [$allowedRoles->firstWhere('name', 'guardian')?->uuid];
            } elseif (empty($this->roles)) {
                $validator->errors()->add('roles', trans('validation.required', ['attribute' => trans('contact.login.props.role')]));
            }

            if (array_diff($this->roles, $allowedRoles->pluck('uuid')->all())) {
                $validator->errors()->add('roles', trans('general.errors.invalid_input'));
            }

            $allowedRoles = $allowedRoles->whereIn('uuid', $this->roles);

            $this->merge(['role_ids' => $allowedRoles->pluck('id')->all()]);
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
