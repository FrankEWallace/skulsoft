<?php

namespace App\Http\Requests\Student;

use App\Enums\Finance\LateFeeFrequency;
use App\Enums\Transport\Direction;
use App\Models\Finance\FeeConcession;
use App\Models\Finance\FeeGroup;
use App\Models\Student\Fee;
use App\Models\Transport\Circle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class UpdateFeeRequest extends FormRequest
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
            'fee_groups' => 'required|array|min:1',
            'fee_groups.*.uuid' => 'required|uuid|distinct',
            'fee_groups.*.fees' => 'required|array',
            'fee_groups.*.fees.*.uuid' => 'required|uuid',
            'fee_groups.*.fees.*.concession' => 'nullable|uuid',
            'fee_groups.*.fees.*.has_transport_fee' => 'boolean',
            'fee_groups.*.fees.*.transport_circle' => 'nullable|uuid',
            'fee_groups.*.fees.*.due_date' => 'required|date_format:Y-m-d',
            'fee_groups.*.fees.*.records.*.custom_amount' => 'required|numeric|min:0',
            'fee_groups.*.fees.*.has_late_fee' => 'boolean',
            'fee_groups.*.fees.*.late_fee_frequency' => ['required_if:fee_groups.*.fees.*.has_late_fee,true', new Enum(LateFeeFrequency::class)],
            'fee_groups.*.fees.*.late_fee_type' => 'required_if:fee_groups.*.fees.*.has_late_fee,true|in:amount,percent',
            'fee_groups.*.fees.*.late_fee_value' => 'required_if:fee_groups.*.fees.*.has_late_fee,true|numeric|min:0',
            'fee_groups.*.fees.*.direction' => ['nullable', 'required_with:fee_groups.*.fees.*.transport_circle', new Enum(Direction::class)],
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $student = $this->route('student');

            $feeGroups = FeeGroup::query()
                ->with('heads')
                ->byPeriod($student->period_id)
                ->where(function ($q) {
                    $q->whereNull('meta->is_custom')
                        ->orWhere('meta->is_custom', '!=', true);
                })
                ->get();

            $studentFees = Fee::query()
                ->select('id', 'uuid')
                ->whereStudentId($student->id)
                ->get();

            $transportCircles = Circle::query()
                ->byPeriod($student->period_id)
                ->get();

            $feeConcessions = FeeConcession::query()
                ->byPeriod($student->period_id)
                ->get();

            $newFeeGroups = [];
            foreach ($this->fee_groups as $index => $feeGroup) {
                $newFeeGroup = $feeGroups->where('uuid', Arr::get($feeGroup, 'uuid'))->first();

                if (! $newFeeGroup) {
                    throw ValidationException::withMessages(['fee_groups.'.$index.'.due_date' => trans('global.could_not_find', ['attribute' => trans('finance.fee_group.fee_group')])]);
                }

                $newFees = [];

                foreach (Arr::get($feeGroup, 'fees', []) as $feeIndex => $fee) {
                    $studentFee = $studentFees->firstWhere('uuid', Arr::get($fee, 'uuid')) ?? [];

                    // Ignore as we will check it in service
                    // if (! $studentFee) {
                    //     throw ValidationException::withMessages(['fee_groups.' . $index . '.fees.' . $feeIndex . '.due_date' => trans('global.could_not_find', ['attribute' => trans('finance.fee_structure.installment')])]);
                    // }

                    $feeConcession = Arr::get($fee, 'concession');

                    if ($feeConcession) {
                        $feeConcession = $feeConcessions->firstWhere('uuid', $feeConcession);

                        if (! $feeConcession) {
                            throw ValidationException::withMessages(['fee_groups.'.$index.'.fees.'.$feeIndex.'.concession' => trans('global.could_not_find', ['attribute' => trans('finance.fee_concession.fee_concession')])]);
                        }
                    }

                    $hasTransportFee = Arr::get($fee, 'has_transport_fee', false);
                    $transportCircle = Arr::get($fee, 'transport_circle');

                    if ($hasTransportFee && $transportCircle) {
                        $transportCircle = $transportCircles->firstWhere('uuid', $transportCircle);

                        if (! $transportCircle) {
                            throw ValidationException::withMessages(['fee_groups.'.$index.'.fees.'.$feeIndex.'.transport_circle' => trans('global.could_not_find', ['attribute' => trans('transport.circle.circle')])]);
                        }
                    }

                    $hasLateFee = Arr::get($fee, 'has_late_fee', false);

                    $lateFee = [
                        'applicable' => $hasLateFee,
                    ];

                    if ($hasLateFee) {
                        $lateFeeType = Arr::get($fee, 'late_fee_type', 'amount');
                        $lateFeeFrequency = Arr::get($fee, 'late_fee_frequency');
                        $lateFeeValue = Arr::get($fee, 'late_fee_value', 0);

                        if ($lateFeeType == 'percent' && $lateFeeValue > 100) {
                            $validator->errors()->add('fee_groups.'.$index.'.fees.'.$feeIndex.'.late_fee_value', trans('validation.exists', ['attribute' => trans('finance.fee_structure.props.late_fee_value')]));
                        }

                        $lateFee['type'] = $lateFeeType;
                        $lateFee['frequency'] = $lateFeeFrequency;
                        $lateFee['value'] = $lateFeeValue;
                    }

                    $newFeeHeads = [];

                    foreach (Arr::get($fee, 'records', []) as $record) {
                        if (! Arr::get($record, 'head')) {
                            continue;
                        }

                        $customAmount = Arr::get($record, 'custom_amount', 0);

                        $feeHead = $newFeeGroup->heads->firstWhere('uuid', Arr::get($record, 'head.uuid'));

                        if (! $feeHead) {
                            $validator->errors()->add('fee_groups.'.$index.'.fees.'.$feeIndex.'.due_date', trans('validation.exists', ['attribute' => trans('finance.fee_head.fee_head')]));
                        }

                        if (Arr::get($record, 'is_optional', false)) {
                            $newFeeHeads[] = [
                                'uuid' => Arr::get($feeHead, 'uuid'),
                                'id' => Arr::get($feeHead, 'id'),
                                'custom_amount' => $customAmount,
                                'is_optional' => true,
                                'is_applicable' => Arr::get($record, 'is_applicable', false),
                            ];
                        } else {
                            $newFeeHeads[] = [
                                'uuid' => Arr::get($feeHead, 'uuid'),
                                'id' => Arr::get($feeHead, 'id'),
                                'custom_amount' => $customAmount,
                                'is_optional' => false,
                                'is_applicable' => true,
                            ];
                        }
                    }

                    $newFees[] = [
                        'uuid' => Arr::get($studentFee, 'uuid'),
                        'due_date' => Arr::get($fee, 'due_date'),
                        'concession' => Arr::get($fee, 'concession'),
                        'has_transport_fee' => Arr::get($fee, 'has_transport_fee'),
                        'transport_circle' => Arr::get($fee, 'transport_circle'),
                        'direction' => Arr::get($fee, 'direction'),
                        'late_fee' => $lateFee,
                        'heads' => $newFeeHeads,
                    ];
                }

                $newFeeGroups[] = [
                    'uuid' => $newFeeGroup->uuid,
                    'id' => $newFeeGroup->id,
                    'fees' => $newFees,
                ];
            }

            $this->merge([
                'fee_groups' => $newFeeGroups,
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
            'fee_groups.*.fees.*.due_date' => __('finance.fee_structure.props.due_date'),
            'fee_groups.*.fees.*.concession' => __('finance.fee_structure.props.concession'),
            'fee_groups.*.fees.*.has_transport_fee' => __('finance.fee_structure.props.has_transport_fee'),
            'fee_groups.*.fees.*.transport_circle' => __('transport.circle.circle'),
            'fee_groups.*.fees.*.records.*.custom_amount' => __('finance.fee_structure.props.amount'),
            'fee_groups.*.fees.*.has_late_fee' => __('finance.fee_structure.props.has_late_fee'),
            'fee_groups.*.fees.*.late_fee_frequency' => __('finance.fee_structure.props.late_fee_frequency'),
            'fee_groups.*.fees.*.late_fee_type' => __('finance.fee_structure.props.late_fee_type'),
            'fee_groups.*.fees.*.late_fee_value' => __('finance.fee_structure.props.late_fee_value'),
            'fee_groups.*.fees.*.has_transport_fee' => __('finance.fee_structure.props.has_transport_fee'),
            'fee_groups.*.fees.*.direction' => __('transport.circle.direction'),
            'fee_groups.*.fees.*.has_late_fee' => __('finance.fee_structure.props.has_late_fee'),
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
            'fee_groups.*.fees.*.late_fee_frequency.required_if' => trans('validation.required', ['attribute' => trans('finance.fee_structure.props.late_fee_frequency')]),
            'fee_groups.*.fees.*.late_fee_frequency.in' => trans('validation.exists', ['attribute' => trans('finance.fee_structure.props.late_fee_frequency')]),
            'fee_groups.*.fees.*.late_fee_type.in' => trans('validation.exists', ['attribute' => trans('finance.fee_structure.props.late_fee_type')]),
            'fee_groups.*.fees.*.late_fee_value.required_if' => trans('validation.required', ['attribute' => trans('finance.fee_structure.props.late_fee_value')]),
            'fee_groups.*.fees.*.late_fee_type.required_if' => trans('validation.required', ['attribute' => trans('finance.fee_structure.props.late_fee_type')]),
            'fee_groups.*.fees.*.direction.required_if' => trans('validation.required', ['attribute' => trans('transport.circle.direction')]),
        ];
    }
}
