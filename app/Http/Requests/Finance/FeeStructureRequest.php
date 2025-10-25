<?php

namespace App\Http\Requests\Finance;

use App\Enums\Finance\LateFeeFrequency;
use App\Models\Finance\FeeGroup;
use App\Models\Finance\FeeStructure;
use App\Models\Transport\Fee as TransportFee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Enum;

class FeeStructureRequest extends FormRequest
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
            'name' => 'required|min:2|max:100',
            'fee_groups' => 'array|required|min:1',
            'fee_groups.*.uuid' => 'required|uuid|distinct',
            'fee_groups.*.installments' => 'array',
            'fee_groups.*.installments.*.uuid' => 'required|uuid|distinct',
            'fee_groups.*.installments.*.title' => 'required|min:2|max:100',
            'fee_groups.*.installments.*.due_date' => 'required|date_format:Y-m-d',
            'fee_groups.*.installments.*.has_transport_fee' => 'boolean',
            'fee_groups.*.installments.*.has_late_fee' => 'boolean',
            'fee_groups.*.installments.*.transport_fee' => 'required_if:fee_groups.*.installments.*.has_transport_fee,true',
            'fee_groups.*.installments.*.late_fee_frequency' => ['required_if:fee_groups.*.installments.*.has_late_fee,true', new Enum(LateFeeFrequency::class)],
            'fee_groups.*.installments.*.late_fee_type' => 'required_if:fee_groups.*.installments.*.has_late_fee,true|in:amount,percent',
            'fee_groups.*.installments.*.late_fee_value' => 'required_if:fee_groups.*.installments.*.has_late_fee,true|numeric|min:0',
            'fee_groups.*.installments.*.heads' => 'array|min:0',
            'fee_groups.*.installments.*.heads.*.uuid' => 'required|uuid',
            'fee_groups.*.installments.*.heads.*.amount' => 'required|numeric|min:0',
            'fee_groups.*.installments.*.heads.*.is_optional' => 'boolean',
            'fee_groups.*.installments.*.heads.*.applicable_to' => 'string|in:new,old,all',
            'fee_groups.*.installments.*.heads.*.applicable_to_gender' => 'string|in:male,female,all',
            'description' => 'nullable|string|max:1000',
        ];

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('fee_structure');

            $existingRecords = FeeStructure::query()
                ->byPeriod()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereName($this->name)
                ->count();

            if ($existingRecords) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => trans('finance.fee_structure.fee_structure')]));
            }

            $transportFees = TransportFee::query()
                ->byPeriod()
                ->select('uuid', 'id')
                ->get();

            $feeGroups = FeeGroup::query()
                ->byPeriod()
                ->with('heads')
                ->get();

            $feeGroupUuids = $feeGroups->pluck('uuid')->all();

            $newFeeGroups = [];
            $totalInstallmentCount = 0;
            foreach ($this->fee_groups as $index => $feeGroup) {
                $uuid = Arr::get($feeGroup, 'uuid');
                if (! in_array($uuid, $feeGroupUuids)) {
                    $validator->errors()->add('fee_groups.'.$index.'.installments.'.$index.'.title', trans('validation.exists', ['attribute' => trans('finance.fee_group.fee_group')]));
                } else {
                    $selectedFeeGroup = $feeGroups->firstWhere('uuid', $uuid);
                    $feeGroup['id'] = $selectedFeeGroup->id;

                    $installmentTitles = [];
                    $newInstallments = [];
                    foreach (Arr::get($feeGroup, 'installments', []) as $installmentIndex => $installment) {
                        $totalInstallmentCount++;
                        $installmentTitles[] = Arr::get($installment, 'title');

                        $newFeeHeads = [];
                        foreach (Arr::get($installment, 'heads', []) as $feeHeadIndex => $feeHead) {
                            $uuid = Arr::get($feeHead, 'uuid');
                            if (! in_array($uuid, $selectedFeeGroup->heads()->pluck('uuid')->all())) {
                                $validator->errors()->add('fee_groups.'.$index.'.installments.'.$installmentIndex.'.heads.'.$feeHeadIndex.'.amount', trans('validation.exists', ['attribute' => trans('finance.fee_head.fee_head')]));
                            } else {
                                $newFeeHeads[] = Arr::add($feeHead, 'id', $selectedFeeGroup->heads()->firstWhere('uuid', $uuid)->id);
                            }
                        }

                        if (Arr::get($installment, 'has_transport_fee')) {
                            if (! in_array(Arr::get($installment, 'transport_fee'), $transportFees->pluck('uuid')->all())) {
                                $validator->errors()->add('fee_groups.'.$index.'.installments.'.$installmentIndex.'.transport_fee', trans('validation.exists', ['attribute' => trans('transport.fee.fee')]));
                            } else {
                                $installment['transport_fee_id'] = $transportFees->firstWhere('uuid', Arr::get($installment, 'transport_fee'))->id;
                            }
                        }

                        $hasLateFee = (bool) Arr::get($installment, 'has_late_fee');

                        $lateFee = [
                            'applicable' => $hasLateFee,
                        ];

                        if ($hasLateFee) {
                            $lateFeeType = Arr::get($installment, 'late_fee_type');
                            $lateFeeFrequency = Arr::get($installment, 'late_fee_frequency');
                            $lateFeeValue = Arr::get($installment, 'late_fee_value', 0);

                            if ($lateFeeType == 'percent' && $lateFeeValue > 100) {
                                $validator->errors()->add('fee_groups.'.$index.'.installments.'.$installmentIndex.'.late_fee_value', trans('validation.exists', ['attribute' => trans('finance.fee_structure.props.late_fee_value')]));
                            }

                            $lateFee['type'] = $lateFeeType;
                            $lateFee['frequency'] = $lateFeeFrequency;
                            $lateFee['value'] = $lateFeeValue;
                        }

                        $installment['late_fee'] = $lateFee;
                        $installment['heads'] = $newFeeHeads;
                        $newInstallments[] = $installment;

                        if (count(Arr::get($feeGroup, 'heads')) === 0) {
                            if (! Arr::get($installment, 'has_transport_fee')) {
                                $validator->errors()->add('fee_groups.'.$index.'.installments.'.$installmentIndex.'.title', trans('validation.required', ['attribute' => trans('finance.fee_head.fee_head')]));
                            }
                        }
                    }

                    if (count($installmentTitles) > count(array_unique($installmentTitles))) {
                        $validator->errors()->add('message', trans('finance.fee_structure.duplicate_title_found', ['attribute' => Arr::get($feeGroup, 'name')]));
                    }

                    $feeGroup['installments'] = $newInstallments;
                    $newFeeGroups[] = $feeGroup;
                }
            }

            if ($totalInstallmentCount === 0) {
                $validator->errors()->add('name', trans('global.could_not_find', ['attribute' => trans('finance.fee_structure.installment')]));
            }

            $this->merge(['fee_groups' => $newFeeGroups]);
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
            'name' => __('finance.fee_structure.props.name'),
            'description' => __('finance.fee_structure.props.description'),
            'fee_groups.*.installments.*.title' => __('finance.fee_structure.props.title'),
            'fee_groups.*.installments.*.due_date' => __('finance.fee_structure.props.due_date'),
            'fee_groups.*.installments.*.late_fee_frequency' => __('finance.fee_structure.props.late_fee_frequency'),
            'fee_groups.*.installments.*.late_fee_type' => __('finance.fee_structure.props.late_fee_type'),
            'fee_groups.*.installments.*.late_fee_value' => __('finance.fee_structure.props.late_fee_value'),
            'fee_groups.*.installments.*.has_transport_fee' => __('finance.fee_structure.props.has_transport_fee'),
            'fee_groups.*.installments.*.has_late_fee' => __('finance.fee_structure.props.has_late_fee'),
            'fee_groups.*.installments.*.heads.*.amount' => __('finance.fee_structure.props.amount'),
            'fee_groups.*.installments.*.heads.*.is_optional' => __('finance.fee_structure.props.is_optional'),
            'fee_groups.*.installments.*.heads.*.applicable_to' => __('finance.fee_structure.props.applicable_to'),
            'fee_groups.*.installments.*.heads.*.applicable_to_gender' => __('finance.fee_structure.props.applicable_to_gender'),
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
            'fee_groups.*.installments.*.transport_fee.required_if' => trans('validation.required', ['attribute' => trans('transport.fee.fee')]),
            'fee_groups.*.installments.*.late_fee_frequency.required_if' => trans('validation.required', ['attribute' => trans('finance.fee_structure.props.late_fee_frequency')]),
            'fee_groups.*.installments.*.late_fee_frequency.in' => trans('validation.exists', ['attribute' => trans('finance.fee_structure.props.late_fee_frequency')]),
            'fee_groups.*.installments.*.late_fee_type.in' => trans('validation.exists', ['attribute' => trans('finance.fee_structure.props.late_fee_type')]),
            'fee_groups.*.installments.*.late_fee_value.required_if' => trans('validation.required', ['attribute' => trans('finance.fee_structure.props.late_fee_value')]),
        ];
    }
}
