<?php

namespace App\Http\Requests\Academic;

use App\Domain\Academic\Models\Course;
use App\Domain\Academic\Models\Division;
use Illuminate\Foundation\Http\FormRequest;

class CourseRequest extends FormRequest
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
            'name' => ['required', 'max:200'],
            'term' => ['nullable', 'max:200'],
            'division' => 'required',
            'code' => ['nullable', 'string', 'max:50'],
            'shortcode' => ['nullable', 'string', 'max:50'],
            'enable_registration' => 'boolean',
            'registration_fee' => 'required_if:enable_registration,true|numeric|min:0',
            'batch_with_same_subject' => 'boolean',
            // 'position' => ['required', 'integer', 'min:0', 'max:1000'],
            'pg_account' => ['nullable', 'max:100'],
            'description' => 'nullable|min:2|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('course');

            $division = Division::query()
                ->byPeriod()
                ->filterAccessible()
                ->where('uuid', $this->division)
                ->getOrFail(trans('academic.division.division'), 'division');

            $existingRecords = Course::query()
                ->whereDivisionId($division->id)
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereName($this->name)
                ->whereTerm($this->term)
                ->exists();

            if ($existingRecords) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => __('academic.division.props.name')]));
            }

            $this->whenFilled('code', function (string $input) use ($validator, $division, $uuid) {
                $existingCodes = Course::query()
                    ->byPeriod()
                    ->when($uuid, function ($q, $uuid) {
                        $q->where('uuid', '!=', $uuid);
                    })
                    ->whereDivisionId($division?->id)
                    ->whereCode($input)
                    ->exists();

                if ($existingCodes) {
                    $validator->errors()->add('code', trans('validation.unique', ['attribute' => trans('academic.course.course')]));
                }
            });

            $this->whenFilled('shortcode', function (string $input) {
                // Can have duplicate shortcodes
                // $existingShortcodes = Course::query()
                //     ->byPeriod()
                //     ->when($uuid, function ($q, $uuid) {
                //         $q->where('uuid', '!=', $uuid);
                //     })
                //     ->whereDivisionId($division?->id)
                //     ->whereShortcode($input)
                //     ->exists();

                // if ($existingShortcodes) {
                //     $validator->errors()->add('shortcode', trans('validation.unique', ['attribute' => trans('academic.course.course')]));
                // }
            });

            $this->merge([
                'position' => 0,
                'division_id' => $division->id,
                'registration_fee' => $this->boolean('enable_registration') ? $this->registration_fee : 0,
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
            'name' => __('academic.course.props.name'),
            'division' => __('academic.division.division'),
            'code' => __('academic.course.props.code'),
            'shortcode' => __('academic.course.props.shortcode'),
            'enable_registration' => __('academic.course.props.enable_registration'),
            'registration_fee' => __('academic.course.props.registration_fee'),
            'batch_with_same_subject' => __('academic.course.props.batch_with_same_subject'),
            'position' => __('general.position'),
            'pg_account' => __('finance.config.props.pg_account'),
            'description' => __('academic.course.props.description'),
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
            'registration_fee.required_if' => __('validation.required', ['attribute' => __('academic.course.props.registration_fee')]),
        ];
    }
}
