<?php

namespace App\Http\Requests\Student;

use App\Models\Finance\FeeHead;
use Illuminate\Foundation\Http\FormRequest;

class CustomFeeRequest extends FormRequest
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
            'fee_head' => 'required|uuid',
            'amount' => 'required|numeric|min:0.01',
            'due_date' => 'required|date_format:Y-m-d',
            'remarks' => 'nullable|max:200',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $studentUuid = $this->route('student');

            $feeHead = FeeHead::query()
                ->byPeriod()
                ->whereHas('group', function ($q) {
                    $q->where('meta->is_custom', true);
                })
                ->whereUuid($this->fee_head)
                ->getOrFail(trans('student.fee.custom_fee'));

            $this->merge([
                'fee_head_id' => $feeHead->id,
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
            'fee_head' => __('student.fee.custom_fee'),
            'amount' => __('student.fee.props.amount'),
            'due_date' => __('student.fee.props.due_date'),
            'remarks' => __('student.fee.props.remarks'),
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
