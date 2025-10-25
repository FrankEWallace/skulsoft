<?php

namespace App\Http\Requests\Resource;

use App\Models\Academic\Batch;
use App\Models\Academic\Subject;
use App\Models\Resource\LessonPlan;
use Illuminate\Foundation\Http\FormRequest;

class LessonPlanRequest extends FormRequest
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
            'topic' => 'required|max:255',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'details' => 'required|array|min:1',
            'details.*.heading' => 'required|min:2|max:255|distinct',
            'details.*.description' => 'nullable|max:10000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new LessonPlan)->getModelName();

            $lessonPlanUuid = $this->route('lesson_plan.uuid');

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
            'topic' => __('resource.lesson_plan.props.topic'),
            'start_date' => __('resource.lesson_plan.props.start_date'),
            'end_date' => __('resource.lesson_plan.props.end_date'),
            'details' => __('resource.lesson_plan.props.details'),
            'details.*.heading' => __('resource.lesson_plan.props.heading'),
            '.*.description' => __('resource.lesson_plan.props.description'),
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
