<?php

namespace App\Http\Requests\Academic;

use App\Domain\Academic\Models\Department;
use App\Domain\Academic\Models\Program;
use App\Domain\Academic\Models\ProgramType;
use Illuminate\Foundation\Http\FormRequest;

class ProgramRequest extends FormRequest
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
            'type' => ['nullable', 'uuid'],
            'department' => ['nullable', 'uuid'],
            'alias' => ['nullable', 'string', 'max:100'],
            'enable_registration' => ['boolean'],
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
            $uuid = $this->route('program');

            $type = $this->type ? ProgramType::query()
                ->byTeam()
                ->where('uuid', $this->type)
                ->getOrFail(trans('academic.program_type.program_type')) : null;

            $department = $this->department ? Department::query()
                ->byTeam()
                ->where('uuid', $this->department)
                ->getOrFail(trans('academic.department.department')) : null;

            $existingNames = Program::query()
                ->byTeam()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereName($this->name)
                ->where('department_id', $department?->id)
                ->exists();

            if ($existingNames) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => trans('academic.program.program')]));
            }

            $this->whenFilled('code', function (string $input) use ($validator, $uuid) {
                $existingCodes = Program::query()
                    ->byTeam()
                    ->when($uuid, function ($q, $uuid) {
                        $q->where('uuid', '!=', $uuid);
                    })
                    ->whereCode($input)
                    ->exists();

                if ($existingCodes) {
                    $validator->errors()->add('code', trans('validation.unique', ['attribute' => trans('academic.program.program')]));
                }
            });

            $this->whenFilled('shortcode', function (string $input) use ($validator, $uuid) {
                $existingCodes = Program::query()
                    ->byTeam()
                    ->when($uuid, function ($q, $uuid) {
                        $q->where('uuid', '!=', $uuid);
                    })
                    ->whereShortcode($input)
                    ->exists();

                if ($existingCodes) {
                    $validator->errors()->add('shortcode', trans('validation.unique', ['attribute' => trans('academic.program.program')]));
                }
            });

            $this->whenFilled('alias', function (string $input) use ($validator, $uuid) {
                $existingAliases = Program::query()
                    ->byTeam()
                    ->when($uuid, function ($q, $uuid) {
                        $q->where('uuid', '!=', $uuid);
                    })
                    ->whereAlias($input)
                    ->exists();

                if ($existingAliases) {
                    $validator->errors()->add('alias', trans('validation.unique', ['attribute' => trans('academic.program.program')]));
                }
            });

            $this->merge([
                'type_id' => $type?->id,
                'department_id' => $department?->id,
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
            'name' => __('academic.program.props.name'),
            'code' => __('academic.program.props.code'),
            'shortcode' => __('academic.program.props.shortcode'),
            'alias' => __('academic.program.props.alias'),
            'department' => __('academic.department.department'),
            'enable_registration' => __('student.online_registration.enable_registration'),
            'description' => __('academic.program.props.description'),
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
