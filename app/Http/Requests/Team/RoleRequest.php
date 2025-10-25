<?php

namespace App\Http\Requests\Team;

use App\Models\Team\Role;
use Illuminate\Foundation\Http\FormRequest;

class RoleRequest extends FormRequest
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
            'name' => ['required', 'max:30', 'min:3'],
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
            'name' => __('team.config.role.props.name'),
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

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $team = $this->route('team');

            if (! $team) {
                $validator->errors()->add('message', trans('global.could_not_find', ['attribute' => trans('team.team')]));

                return;
            }

            $roleCount = Role::whereName($this->name)->where(function ($q) use ($team) {
                $q->whereNull('team_id')->orWhere('team_id', $team->id);
            })->count();

            if ($roleCount) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => trans('team.config.role.props.name')]));
            }
        });
    }
}
