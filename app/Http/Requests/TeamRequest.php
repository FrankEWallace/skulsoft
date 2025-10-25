<?php

namespace App\Http\Requests;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeamRequest extends FormRequest
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
        $uuid = $this->route('team');

        return [
            'name' => ['required', 'max:100', 'min:3', Rule::unique('teams')->ignore($uuid)],
            'code' => ['required', 'max:50', 'min:1', 'string', Rule::unique('teams')->ignore($uuid)],
            'organization' => ['nullable', 'uuid'],
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('team');

            $organization = $this->organization
                ? Organization::query()
                    ->where('uuid', $this->organization)
                    ->getOrFail(trans('organization.organization'), 'organization')
                : null;

            $this->merge([
                'organization_id' => $organization?->id,
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
            'name' => __('team.props.name'),
            'code' => __('team.props.code'),
            'organization' => __('organization.organization'),
        ];
    }
}
