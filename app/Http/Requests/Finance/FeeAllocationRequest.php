<?php

namespace App\Http\Requests\Finance;

use App\Models\Academic\Batch;
use App\Models\Academic\Course;
use Illuminate\Foundation\Http\FormRequest;

class FeeAllocationRequest extends FormRequest
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
            'type' => 'required|in:course,batch',
            'courses' => 'required_if:type,course|array',
            'batches' => 'required_if:type,batch|array',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $feeStructure = $this->route('fee_structure');

            if ($this->type === 'course') {
                $courses = Course::query()
                    ->byPeriod($feeStructure->period_id)
                    ->filterAccessible()
                    ->whereIn('uuid', $this->courses)
                    ->get()
                    ->pluck('id')
                    ->all();

                $this->merge(['course_ids' => $courses]);
            } elseif ($this->type === 'batch') {
                $batches = Batch::query()
                    ->byPeriod($feeStructure->period_id)
                    ->filterAccessible()
                    ->whereIn('uuid', $this->batches)
                    ->get()
                    ->pluck('id')
                    ->all();

                $this->merge(['batch_ids' => $batches]);
            }
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
            'type' => __('general.type'),
            'courses' => __('academic.course.course'),
            'batches' => __('academic.batch.batch'),
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
            'courses.required_if' => trans('validation.required', ['attribute' => trans('academic.course.course')]),
            'batches.required_if' => trans('validation.required', ['attribute' => trans('academic.batch.batch')]),
        ];
    }
}
