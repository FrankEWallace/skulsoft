<?php

namespace App\Http\Requests\Resource;

use App\Models\Academic\Batch;
use App\Models\Academic\Subject;
use App\Models\Resource\Diary;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class DiaryRequest extends FormRequest
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
            'subject' => 'nullable|uuid',
            'date' => 'required|date_format:Y-m-d',
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
            $mediaModel = (new Diary)->getModelName();

            $diaryUuid = $this->route('diary');

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

            // Could not check becase of multiple batches
            // $existingRecord = Diary::query()
            //     ->where('batch_id', $batch->id)
            //     ->where('subject_id', $subject?->id)
            //     ->where('date', $this->date)
            //     ->where('uuid', '!=', $diaryUuid)
            //     ->exists();

            // if ($existingRecord) {
            //     throw ValidationException::withMessages(['message' => trans('resource.diary.duplicate_record')]);
            // }

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
            'date' => __('resource.diary.props.date'),
            'details' => __('resource.diary.props.details'),
            'details.*.heading' => __('resource.diary.props.heading'),
            '.*.description' => __('resource.diary.props.description'),
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
