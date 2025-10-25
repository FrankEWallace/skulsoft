<?php

namespace App\Http\Requests\Student;

use App\Models\Academic\Batch;
use App\Models\Academic\Period;
use Illuminate\Foundation\Http\FormRequest;

class PromotionRequest extends FormRequest
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
            'students' => ['required', 'array'],
            'mark_as_alumni' => ['boolean'],
        ];

        if (! $this->mark_as_alumni) {
            $rules['period'] = ['required', 'uuid'];
            $rules['date'] = ['required', 'date'];
            $rules['new_batch'] = ['required', 'uuid'];
        } else {
            $rules['date'] = ['required', 'date'];
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $currentPeriod = Period::query()
                ->byTeam()
                ->whereId(auth()->user()->current_period_id)
                ->first();

            if ($this->mark_as_alumni) {
                return;
            }

            $period = Period::query()
                ->byTeam()
                ->where('id', '!=', auth()->user()->current_period_id)
                ->whereUuid($this->period)
                ->getOrFail(trans('academic.period.period'), 'period');

            if ($period->start_date->value < $currentPeriod->start_date->value) {
                $validator->errors()->add('period', trans('student.promotion.period_date_before_current_period'));
            }

            if ($this->date < $period->start_date->value) {
                $validator->errors()->add('date', trans('student.promotion.date_before_current_period'));
            }

            $batch = Batch::query()
                ->byPeriod($period->id)
                ->filterAccessible()
                ->whereUuid($this->new_batch)
                ->getOrFail(trans('academic.batch.batch'), 'batch');

            $this->merge([
                'period_id' => $period->id,
                'batch_id' => $batch->id,
                'course_id' => $batch->course_id,
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
            'students' => __('student.student'),
            'date' => __('student.promotion.props.date'),
            'batch' => __('academic.batch.batch'),
            'period' => __('academic.period.period'),
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
            //
        ];
    }
}
