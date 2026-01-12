<?php

namespace App\Http\Requests\Academic;

use App\Domain\Academic\Models\Department;
use Illuminate\Foundation\Http\FormRequest;

class DepartmentRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'code' => ['nullable', 'string', 'max:50'],
            'shortcode' => ['nullable', 'string', 'max:50'],
            'alias' => ['nullable', 'string', 'max:100'],
            'description' => 'nullable|string|max:1000',
        ];

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('department');

            $existingNames = Department::query()
                ->byTeam()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereName($this->name)
                ->exists();

            if ($existingNames) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => trans('academic.department.department')]));
            }

            $this->whenFilled('code', function (string $input) use ($validator, $uuid) {
                $existingCodes = Department::query()
                    ->byTeam()
                    ->when($uuid, function ($q, $uuid) {
                        $q->where('uuid', '!=', $uuid);
                    })
                    ->whereCode($input)
                    ->exists();

                if ($existingCodes) {
                    $validator->errors()->add('code', trans('validation.unique', ['attribute' => trans('academic.department.department')]));
                }
            });

            $this->whenFilled('shortcode', function (string $input) use ($validator, $uuid) {
                $existingCodes = Department::query()
                    ->byTeam()
                    ->when($uuid, function ($q, $uuid) {
                        $q->where('uuid', '!=', $uuid);
                    })
                    ->whereShortcode($input)
                    ->exists();

                if ($existingCodes) {
                    $validator->errors()->add('shortcode', trans('validation.unique', ['attribute' => trans('academic.department.department')]));
                }
            });

            $this->whenFilled('alias', function (string $input) use ($validator, $uuid) {
                $existingAliases = Department::query()
                    ->byTeam()
                    ->when($uuid, function ($q, $uuid) {
                        $q->where('uuid', '!=', $uuid);
                    })
                    ->whereAlias($input)
                    ->exists();

                if ($existingAliases) {
                    $validator->errors()->add('alias', trans('validation.unique', ['attribute' => trans('academic.department.department')]));
                }
            });
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
            'name' => __('academic.department.props.name'),
            'code' => __('academic.department.props.code'),
            'shortcode' => __('academic.department.props.shortcode'),
            'alias' => __('academic.department.props.alias'),
            'enable_registration' => __('student.online_registration.enable_registration'),
            'description' => __('academic.department.props.description'),
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
