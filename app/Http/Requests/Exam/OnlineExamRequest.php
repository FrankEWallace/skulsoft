<?php

namespace App\Http\Requests\Exam;

use App\Enums\Exam\OnlineExamType;
use App\Helpers\CalHelper;
use App\Domain\Academic\Models\Batch;
use App\Domain\Academic\Models\Subject;
use App\Models\Exam\OnlineExam;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class OnlineExamRequest extends FormRequest
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
        $rules = [
            'title' => ['required', 'min:2', 'max:255'],
            'type' => ['required', new Enum(OnlineExamType::class)],
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i:s'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date'],
            'end_time' => ['required', 'date_format:H:i:s'],
            'batches' => 'array|min:1',
            'subject' => 'nullable|uuid',
            'pass_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'has_negative_marking' => ['boolean'],
            'negative_mark_percent_per_question' => ['required_if:has_negative_marking,true', 'numeric', 'min:0', 'max:100'],
            'instructions' => ['nullable', 'min:2', 'max:10000'],
            'description' => ['nullable', 'min:2', 'max:10000'],
        ];

        if (! $this->end_date) {
            $rules['end_time'] = ['required', 'date_format:H:i:s', 'after:start_time'];
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('online_exam');

            $dateTime = Carbon::parse(CalHelper::storeDateTime($this->date.' '.$this->start_time));

            if ($dateTime->isPast()) {
                $validator->errors()->add('date', trans('validation.after', ['attribute' => __('exam.online_exam.props.date'), 'date' => \Cal::dateTime(now()->toDateTimeString())->formatted]));
            }

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

            $existingRecords = OnlineExam::query()
                ->byPeriod()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereTitle($this->title)
                ->whereDate('date', $this->date)
                ->exists();

            if ($existingRecords) {
                $validator->errors()->add('title', trans('validation.unique', ['attribute' => __('exam.online_exam.props.title')]));
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
            'title' => __('exam.online_exam.props.title'),
            'type' => __('exam.online_exam.props.type'),
            'date' => __('exam.online_exam.props.date'),
            'start_time' => __('exam.online_exam.props.start_time'),
            'end_date' => __('exam.online_exam.props.end_date'),
            'end_time' => __('exam.online_exam.props.end_time'),
            'batches' => __('academic.batch.batch'),
            'subject' => __('academic.subject.subject'),
            'pass_percentage' => __('exam.online_exam.props.pass_percentage'),
            'has_negative_marking' => __('exam.online_exam.props.has_negative_marking'),
            'negative_mark_percent_per_question' => __('exam.online_exam.props.negative_mark_percent_per_question'),
            'instructions' => __('exam.online_exam.props.instructions'),
            'description' => __('exam.online_exam.props.description'),
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
            'negative_mark_percent_per_question.required_if' => __('validation.required', ['attribute' => __('exam.online_exam.props.negative_mark_percent_per_question')]),
        ];
    }
}
