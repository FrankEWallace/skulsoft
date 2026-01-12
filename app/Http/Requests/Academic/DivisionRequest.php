<?php

namespace App\Http\Requests\Academic;

use App\Domain\Academic\Models\Division;
use App\Domain\Academic\Models\Program;
use Illuminate\Foundation\Http\FormRequest;

class DivisionRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:50'],
            'shortcode' => ['nullable', 'string', 'max:50'],
            'program' => ['required', 'uuid'],
            // 'position' => ['required', 'integer', 'min:0', 'max:1000'],
            'pg_account' => ['nullable', 'max:100'],
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
            $uuid = $this->route('division');

            $program = Program::query()
                ->byTeam()
                ->where('uuid', $this->program)
                ->getOrFail(trans('academic.program.program'));

            $existingRecords = Division::query()
                ->byPeriod()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereName($this->name)
                ->where('program_id', $program->id)
                ->exists();

            if ($existingRecords) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => trans('academic.division.division')]));
            }

            $this->whenFilled('code', function (string $input) use ($validator, $uuid) {
                $existingCodes = Division::query()
                    ->byPeriod()
                    ->when($uuid, function ($q, $uuid) {
                        $q->where('uuid', '!=', $uuid);
                    })
                    ->whereCode($input)
                    ->exists();

                if ($existingCodes) {
                    $validator->errors()->add('code', trans('validation.unique', ['attribute' => trans('academic.division.division')]));
                }
            });

            $this->whenFilled('shortcode', function (string $input) {
                // Can have duplicate shortcodes
                // $existingShortcodes = Division::query()
                //     ->byPeriod()
                //     ->when($uuid, function ($q, $uuid) {
                //         $q->where('uuid', '!=', $uuid);
                //     })
                //     ->whereShortcode($input)
                //     ->exists();

                // if ($existingShortcodes) {
                //     $validator->errors()->add('shortcode', trans('validation.unique', ['attribute' => trans('academic.division.division')]));
                // }
            });

            $this->merge([
                'position' => 0,
                'program_id' => $program?->id,
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
            'name' => __('academic.division.props.name'),
            'code' => __('academic.division.props.code'),
            'shortcode' => __('academic.division.props.shortcode'),
            'program' => __('academic.program.program'),
            'position' => __('general.position'),
            'pg_account' => __('finance.config.props.pg_account'),
            'description' => __('academic.division.props.description'),
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
