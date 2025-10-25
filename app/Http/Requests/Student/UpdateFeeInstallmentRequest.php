<?php

namespace App\Http\Requests\Student;

use App\Enums\Finance\LateFeeFrequency;
use App\Enums\Transport\Direction;
use App\Models\Transport\Circle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateFeeInstallmentRequest extends FormRequest
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
            'due_date' => 'required|date_format:Y-m-d',
            'has_transport_fee' => 'boolean',
            'has_late_fee' => 'boolean',
            'late_fee_frequency' => ['required_if:has_late_fee,true', new Enum(LateFeeFrequency::class)],
            // 'late_fee_type' => 'required_if:has_late_fee,true|in:amount,percent',
            'late_fee_value' => 'required_if:has_late_fee,true|numeric|min:0',
            'heads' => 'required|array|min:1',
            'direction' => ['nullable', 'required_with:transport_circle', new Enum(Direction::class)],
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $student = $this->route('student');

            // $transportCircle = null;
            // if ($this->has_transport_fee && $this->transport_circle) {
            //     $transportCircle = Circle::query()
            //         ->byPeriod($student->period_id)
            //         ->whereUuid($this->transport_circle)
            //         ->getOrFail(trans('transport.circle.circle'), 'transport_circle');
            // }

            // $this->merge([
            //     'transport_circle' => $transportCircle?->id,
            // ]);
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
            'due_date' => __('finance.fee_structure.props.due_date'),
            'has_transport_fee' => __('finance.fee_structure.props.has_transport_fee'),
            'late_fee_frequency' => __('finance.fee_structure.props.late_fee_frequency'),
            'late_fee_type' => __('finance.fee_structure.props.late_fee_type'),
            'late_fee_value' => __('finance.fee_structure.props.late_fee_value'),
            'has_late_fee' => __('finance.fee_structure.props.has_late_fee'),
            'heads.*.amount' => __('finance.fee_structure.props.amount'),
            'direction' => __('transport.circle.direction'),
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
            'late_fee_frequency.required_if' => trans('validation.required', ['attribute' => trans('finance.fee_structure.props.late_fee_frequency')]),
            'late_fee_frequency.in' => trans('validation.exists', ['attribute' => trans('finance.fee_structure.props.late_fee_frequency')]),
            'late_fee_type.in' => trans('validation.exists', ['attribute' => trans('finance.fee_structure.props.late_fee_type')]),
            'late_fee_value.required_if' => trans('validation.required', ['attribute' => trans('finance.fee_structure.props.late_fee_value')]),
            'direction.required_with' => trans('validation.exists', ['attribute' => trans('transport.circle.direction')]),
        ];
    }
}
