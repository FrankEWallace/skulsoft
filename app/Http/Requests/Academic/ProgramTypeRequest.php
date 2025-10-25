<?php

namespace App\Http\Requests\Academic;

use App\Models\Academic\ProgramType;
use Illuminate\Foundation\Http\FormRequest;

class ProgramTypeRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:3', 'max:50'],
            'code' => ['nullable', 'string', 'max:50'],
            'shortcode' => ['nullable', 'string', 'max:50'],
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
            $uuid = $this->route('program_type');

            $existingRecords = ProgramType::query()
                ->byTeam()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereName($this->name)
                ->exists();

            if ($existingRecords) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => trans('academic.program_type.program_type')]));
            }

            $this->whenFilled('code', function (string $input) use ($validator, $uuid) {
                $existingCodes = ProgramType::query()
                    ->byTeam()
                    ->when($uuid, function ($q, $uuid) {
                        $q->where('uuid', '!=', $uuid);
                    })
                    ->whereCode($input)
                    ->exists();

                if ($existingCodes) {
                    $validator->errors()->add('code', trans('validation.unique', ['attribute' => trans('academic.program_type.program_type')]));
                }
            });

            $this->whenFilled('shortcode', function (string $input) use ($validator, $uuid) {
                $existingShortcodes = ProgramType::query()
                    ->byTeam()
                    ->when($uuid, function ($q, $uuid) {
                        $q->where('uuid', '!=', $uuid);
                    })
                    ->whereShortcode($input)
                    ->exists();

                if ($existingShortcodes) {
                    $validator->errors()->add('shortcode', trans('validation.unique', ['attribute' => trans('academic.program_type.program_type')]));
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
            'name' => __('academic.program_type.props.name'),
            'code' => __('academic.program_type.props.code'),
            'shortcode' => __('academic.program_type.props.shortcode'),
            'description' => __('academic.program_type.props.description'),
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
