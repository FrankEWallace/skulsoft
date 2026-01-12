<?php

namespace App\Http\Requests\Exam;

use App\Domain\Academic\Models\Division;
use App\Models\Exam\Term;
use Illuminate\Foundation\Http\FormRequest;

class TermRequest extends FormRequest
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
            'name' => ['required', 'min:2', 'max:100'],
            'division' => 'nullable|uuid',
            'display_name' => 'nullable|min:1|max:50',
            // 'position' => 'required|integer|min:1|max:100',
            'description' => 'nullable|min:2|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('term.uuid');

            $division = $this->division ? Division::query()
                ->byPeriod()
                ->filterAccessible()
                ->where('uuid', $this->division)
                ->getOrFail(__('academic.division.division'), 'division') : null;

            $existingRecords = Term::query()
                ->byPeriod()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->where('division_id', $division?->id)
                ->whereName($this->name)
                ->exists();

            if ($existingRecords) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => __('exam.term.props.name')]));
            }

            if (! $this->division) {
                $existingRecords = Term::query()
                    ->byPeriod()
                    ->when($uuid, function ($q, $uuid) {
                        $q->where('uuid', '!=', $uuid);
                    })
                    ->whereNotNull('division_id')
                    ->whereName($this->name)
                    ->exists();

                if ($existingRecords) {
                    $validator->errors()->add('name', trans('validation.unique', ['attribute' => __('exam.term.props.name')]));
                }
            } else {
                $existingRecords = Term::query()
                    ->byPeriod()
                    ->when($uuid, function ($q, $uuid) {
                        $q->where('uuid', '!=', $uuid);
                    })
                    ->whereNull('division_id')
                    ->whereName($this->name)
                    ->exists();

                if ($existingRecords) {
                    $validator->errors()->add('name', trans('validation.unique', ['attribute' => __('exam.term.props.name')]));
                }
            }

            $this->merge([
                'division_id' => $division?->id,
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
            'name' => __('exam.term.props.name'),
            'division' => __('academic.division.division'),
            'display_name' => __('exam.term.props.display_name'),
            // 'position' => __('exam.term.props.position'),
            'description' => __('exam.term.props.description'),
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
