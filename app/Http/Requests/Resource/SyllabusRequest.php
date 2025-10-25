<?php

namespace App\Http\Requests\Resource;

use App\Models\Academic\Batch;
use App\Models\Academic\Subject;
use App\Models\Resource\Syllabus;
use Illuminate\Foundation\Http\FormRequest;

class SyllabusRequest extends FormRequest
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
            'batches' => 'array|min:1',
            'subject' => 'required|uuid',
            'remarks' => 'nullable|max:1000',
            'units' => 'required|array|min:1',
            'units.*.unit_number' => 'nullable|string|distinct|max:20',
            'units.*.unit_name' => 'required|min:2|max:255|distinct',
            'units.*.start_date' => 'required|date_format:Y-m-d',
            'units.*.end_date' => 'required|date_format:Y-m-d|after_or_equal:units.*.start_date',
            'units.*.completion_date' => 'nullable|date_format:Y-m-d|after_or_equal:units.*.start_date',
            'units.*.description' => 'nullable|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new Syllabus)->getModelName();

            $syllabusUuid = $this->route('syllabus.uuid');

            $batches = Batch::query()
                ->byPeriod()
                ->filterAccessible()
                ->whereIn('uuid', $this->batches)
                ->listOrFail(trans('academic.batch.batch'), 'batches');

            $subject = null;
            foreach ($batches as $batch) {
                $subject = Subject::query()
                    ->findByBatchOrFail($batch->id, $batch->course_id, $this->subject);
            }

            $this->merge([
                'batch_ids' => $batches->pluck('id')->all(),
                'subject_id' => $subject?->id,
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
            'batches' => __('academic.batch.batch'),
            'subject' => __('academic.subject.subject'),
            'remarks' => __('resource.syllabus.props.remarks'),
            'units' => __('resource.syllabus.props.units'),
            'units.*.unit_number' => __('resource.syllabus.props.unit_number'),
            'units.*.unit_name' => __('resource.syllabus.props.unit_name'),
            'units.*.start_date' => __('resource.syllabus.props.start_date'),
            'units.*.end_date' => __('resource.syllabus.props.end_date'),
            'units.*.completion_date' => __('resource.syllabus.props.completion_date'),
            '.*.description' => __('resource.syllabus.props.description'),
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
