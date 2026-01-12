<?php

namespace App\Http\Requests\Resource;

use App\Enums\OptionType;
use App\Domain\Academic\Models\Batch;
use App\Domain\Academic\Models\Subject;
use App\Models\Option;
use App\Models\Resource\Assignment;
use Illuminate\Foundation\Http\FormRequest;

class AssignmentRequest extends FormRequest
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
            'title' => 'required|max:255',
            'type' => 'required|uuid',
            'batches' => 'array|min:1',
            'subject' => 'nullable|uuid',
            'date' => 'required|date_format:Y-m-d',
            'due_date' => 'required|date_format:Y-m-d|after_or_equal:date',
            'description' => 'nullable|min:2|max:10000',
        ];

        if ($this->enable_marking) {
            $rules['max_mark'] = 'required|integer|min:0';
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new Assignment)->getModelName();

            $assignmentUuid = $this->route('assignment');

            $type = $this->type ? Option::query()
                ->byTeam()
                ->whereType(OptionType::ASSIGNMENT_TYPE->value)
                ->whereUuid($this->type)
                ->getOrFail(__('resource.assignment.type.type'), 'type') : null;

            $batches = Batch::query()
                ->byPeriod()
                ->filterAccessible()
                ->whereIn('uuid', $this->batches)
                ->listOrFail(trans('academic.batch.batch'), 'batches');

            $subject = null;
            if ($this->subject) {
                foreach ($batches as $batch) {
                    $subject = Subject::query()
                        ->findByBatchOrFail($batch->id, $batch->course_id, $this->subject);
                }
            }

            $this->merge([
                'type_id' => $type?->id,
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
            'title' => __('resource.assignment.props.title'),
            'type' => __('resource.assignment.type.type'),
            'batches' => __('academic.batch.batch'),
            'subject' => __('academic.subject.subject'),
            'date' => __('resource.assignment.props.date'),
            'due_date' => __('resource.assignment.props.due_date'),
            'description' => __('resource.assignment.props.description'),
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
