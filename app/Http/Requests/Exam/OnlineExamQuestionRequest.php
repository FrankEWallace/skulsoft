<?php

namespace App\Http\Requests\Exam;

use App\Enums\Exam\OnlineExamQuestionType;
use App\Models\Exam\OnlineExamQuestion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Enum;

class OnlineExamQuestionRequest extends FormRequest
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
            'type' => ['required', new Enum(OnlineExamQuestionType::class)],
            'mark' => 'required|numeric|min:0.01',
            'title' => 'required|min:2|max:200',
            'header' => 'nullable|min:2|max:10000',
        ];

        if ($this->type === OnlineExamQuestionType::MCQ->value) {
            $rules['options'] = 'required|array|min:2';
            $rules['options.*.title'] = 'required|min:2|max:200|distinct';
            $rules['options.*.is_correct'] = 'boolean';
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $onlineExamUuid = $this->route('online_exam');
            $onlineExamQuestionUuid = $this->route('question');

            $existingQuestion = OnlineExamQuestion::whereHas('exam', function ($q) use ($onlineExamUuid) {
                $q->whereUuid($onlineExamUuid);
            })
                ->when($onlineExamQuestionUuid, function ($q, $onlineExamQuestionUuid) {
                    $q->where('uuid', '!=', $onlineExamQuestionUuid);
                })
                ->whereTitle($this->title)
                ->exists();

            if ($existingQuestion) {
                $validator->errors()->add('title', trans('validation.unique', ['attribute' => __('exam.online_exam.question.props.title')]));
            }

            if ($this->type == OnlineExamQuestionType::MCQ->value && ! collect($this->options)->contains('is_correct', true)) {
                $validator->errors()->add('title', trans('validation.required', ['attribute' => __('exam.online_exam.question.props.correct_answer')]));
            }

            if ($this->type == OnlineExamQuestionType::MCQ->value && collect($this->options)->where('is_correct', true)->count() > 1) {
                $validator->errors()->add('title', trans('validation.required', ['attribute' => __('exam.online_exam.question.props.correct_answer')]));
            }

            $options = collect($this->options)->map(function ($option) {
                return [
                    'uuid' => Arr::get($option, 'uuid'),
                    'title' => Arr::get($option, 'title'),
                    'is_correct' => (bool) Arr::get($option, 'is_correct'),
                ];
            });

            if ($this->type != OnlineExamQuestionType::MCQ->value) {
                $options = [];
            }

            $this->merge([
                'options' => $options,
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
            'type' => __('exam.online_exam.question.props.type'),
            'mark' => __('exam.online_exam.question.props.mark'),
            'title' => __('exam.online_exam.question.props.title'),
            'options' => __('exam.online_exam.question.props.option'),
            'options.*.title' => __('exam.online_exam.question.props.option'),
            'options.*.is_correct' => __('exam.online_exam.question.props.correct_answer'),
            'header' => __('exam.online_exam.question.props.header'),
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
