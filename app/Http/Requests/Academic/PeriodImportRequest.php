<?php

namespace App\Http\Requests\Academic;

use App\Models\Academic\Period;
use Illuminate\Foundation\Http\FormRequest;

class PeriodImportRequest extends FormRequest
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
            'period' => ['required', 'uuid'],
        ];

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $currentPeriod = $this->route('period');

            $period = Period::query()
                ->byTeam()
                ->where('uuid', $this->period)
                ->getOrFail(trans('academic.period.period'));

            if ($period->id == $currentPeriod->id) {
                $validator->errors()->add('period', trans('validation.different', ['attribute' => trans('academic.period.period'), 'other' => trans('academic.period.current_period')]));
            }

            // no longer needed as program is no longer associated with period
            // if ($period->program_id != $currentPeriod->program_id) {
            //     $validator->errors()->add('period', trans('academic.period.could_not_import_from_different_program'));
            // }

            $this->merge([
                'period_id' => $period?->id,
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
        return [];
    }
}
