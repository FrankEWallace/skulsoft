<?php

namespace App\Http\Requests\Academic;

use App\Enums\OptionType;
use App\Domain\Academic\Models\Subject;
use App\Models\Option;
use Illuminate\Foundation\Http\FormRequest;

class SubjectRequest extends FormRequest
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
            'alias' => ['nullable', 'string', 'min:1', 'max:100'],
            'code' => ['nullable', 'alpha_dash', 'min:1', 'max:50'],
            'shortcode' => ['nullable', 'alpha_dash', 'min:1', 'max:50'],
            // 'position' => ['required', 'integer', 'min:0', 'max:1000'],
            'type' => ['nullable', 'uuid'],
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
            $uuid = $this->route('subject');

            $type = $this->type ? Option::query()
                ->byTeam()
                ->whereType(OptionType::SUBJECT_TYPE->value)
                ->whereUuid($this->type)
                ->getOrFail(__('academic.subject.type.type'), 'type') : null;

            $existingRecords = Subject::query()
                ->byPeriod()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereName($this->name)
                ->exists();

            if ($existingRecords) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => trans('academic.subject.subject')]));
            }

            $this->whenFilled('alias', function (string $input) use ($validator, $uuid) {
                $existingRecords = Subject::query()
                    ->byPeriod()
                    ->where('uuid', '!=', $uuid)
                    ->whereAlias($input)
                    ->exists();

                if ($existingRecords) {
                    $validator->errors()->add('alias', __('validation.unique', ['attribute' => config('academic.subject.subject')]));
                }
            });

            $this->whenFilled('code', function (string $input) use ($validator, $uuid) {
                $existingRecords = Subject::query()
                    ->byPeriod()
                    ->where('uuid', '!=', $uuid)
                    ->whereCode($input)
                    ->exists();

                if ($existingRecords) {
                    $validator->errors()->add('code', __('validation.unique', ['attribute' => config('academic.subject.subject')]));
                }
            });

            $this->whenFilled('shortcode', function (string $input) use ($validator, $uuid) {
                $existingRecords = Subject::query()
                    ->byPeriod()
                    ->where('uuid', '!=', $uuid)
                    ->whereShortcode($input)
                    ->exists();

                if ($existingRecords) {
                    $validator->errors()->add('shortcode', __('validation.unique', ['attribute' => config('academic.subject.subject')]));
                }
            });

            $this->merge([
                'position' => 0,
                'type_id' => $type?->id,
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
            'name' => __('academic.subject.props.name'),
            'alias' => __('academic.subject.props.alias'),
            'code' => __('academic.subject.props.code'),
            'shortcode' => __('academic.subject.props.shortcode'),
            'type' => __('academic.subject.type.type'),
            'position' => __('academic.subject.props.position'),
            'description' => __('academic.subject.props.description'),
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
