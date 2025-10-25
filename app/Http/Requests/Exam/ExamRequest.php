<?php

namespace App\Http\Requests\Exam;

use App\Models\Exam\Exam;
use App\Models\Exam\Term;
use Illuminate\Foundation\Http\FormRequest;

class ExamRequest extends FormRequest
{
    /**
     * Deexamine if the user is authorized to make this request.
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
            'code' => ['required', 'min:1', 'max:20'],
            'display_name' => 'nullable|min:1|max:50',
            // 'position' => 'required|integer|min:1|max:100',
            'term' => 'nullable|uuid',
            'description' => 'nullable|min:2|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('exam.uuid');

            $term = $this->term ? Term::query()
                ->byPeriod()
                ->where('uuid', $this->term)
                ->getOrFail(__('exam.term.term'), 'term') : null;

            $existingCodes = Exam::query()
                ->byPeriod()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                // ->whereTermId($term?->id) make it unique for all terms
                ->whereCode($this->code)
                ->exists();

            if ($existingCodes) {
                $validator->errors()->add('code', trans('validation.unique', ['attribute' => trans('exam.props.code')]));
            }

            $existingRecords = Exam::query()
                ->byPeriod()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereTermId($term?->id)
                ->whereName($this->name)
                ->exists();

            if ($existingRecords) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => __('exam.props.name')]));
            }

            $this->merge([
                'term_id' => $term?->id,
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
            'name' => __('exam.props.name'),
            'code' => __('exam.props.code'),
            'term' => __('exam.term.term'),
            'display_name' => __('exam.props.display_name'),
            // 'position' => __('exam.props.position'),
            'description' => __('exam.props.description'),
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
