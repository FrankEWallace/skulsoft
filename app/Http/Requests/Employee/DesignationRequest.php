<?php

namespace App\Http\Requests\Employee;

use App\Models\Employee\Designation;
use Illuminate\Foundation\Http\FormRequest;

class DesignationRequest extends FormRequest
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
            'name' => 'required|min:2|max:100',
            'alias' => 'nullable|min:2|max:100',
            'parent' => 'nullable',
            'description' => 'nullable|min:2|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('designation.uuid');

            $existingNames = Designation::query()
                ->byTeam()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereName($this->name)
                ->exists();

            if ($existingNames) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => __('employee.designation.designation')]));
            }

            if ($this->parent) {
                $parentDesignation = Designation::query()
                    ->byTeam()
                    ->whereUuid($this->parent)
                    ->getOrFail(__('employee.designation.designation'), 'parent');

                $this->merge(['designation_id' => $parentDesignation->id]);
            }

            if (! $this->alias) {
                return;
            }

            $existingAliases = Designation::query()
                ->byTeam()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereAlias($this->alias)
                ->exists();

            if ($existingAliases) {
                $validator->errors()->add('alias', trans('validation.unique', ['attribute' => __('employee.designation.designation')]));
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
            'name' => __('employee.designation.props.name'),
            'alias' => __('employee.designation.props.alias'),
            'parent' => __('employee.designation.props.parent'),
            'description' => __('employee.designation.props.description'),
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
