<?php

namespace App\Http\Requests\Finance;

use App\Enums\Finance\LateFeeFrequency;
use App\Models\Finance\FeeGroup;
use App\Models\Finance\FeeInstallment;
use App\Models\Transport\Fee as TransportFee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class FeeInstallmentRequest extends FormRequest
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
            'title' => 'required|min:2|max:100',
            'due_date' => 'required|date_format:Y-m-d',
            'has_transport_fee' => 'boolean',
            'has_late_fee' => 'boolean',
            'transport_fee' => 'required_if:has_transport_fee,true',
            'late_fee_frequency' => ['required_if:has_late_fee,true', new Enum(LateFeeFrequency::class)],
            'late_fee_type' => 'required_if:has_late_fee,true|in:amount,percent',
            'late_fee_value' => 'required_if:has_late_fee,true|numeric|min:0',
            'heads' => 'required|array|min:1',
            'heads.*.amount' => 'required|numeric|min:0',
            'heads.*.is_optional' => 'boolean',
            'heads.*.applicable_to' => 'string|in:all,new,old',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {

            $feeStructureUuid = $this->route('fee_structure');
            $uuid = $this->route('uuid');

            $feeGroup = FeeGroup::query()
                ->with('heads')
                ->findByUuidOrFail($this->fee_group);

            if ($feeGroup->getMeta('is_custom')) {
                throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
            }

            $existingRecords = FeeInstallment::query()
                ->whereHas('structure', function ($q) use ($feeStructureUuid) {
                    $q->whereUuid($feeStructureUuid);
                })
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereFeeGroupId($feeGroup->id)
                ->whereTitle($this->title)
                ->count();

            if ($existingRecords) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => trans('finance.fee_structure.installment')]));
            }

            $transportFees = TransportFee::query()
                ->byPeriod()
                ->select('uuid', 'id')
                ->get();

            $newFeeHeads = [];
            foreach ($this->heads as $index => $feeHead) {
                $uuid = Arr::get($feeHead, 'uuid');
                if (! in_array($uuid, $feeGroup->heads()->pluck('uuid')->all())) {
                    $validator->errors()->add('heads.'.$index.'.amount', trans('validation.exists', ['attribute' => trans('finance.fee_head.fee_head')]));
                } else {
                    $feeHead = Arr::except($feeHead, ['created_at', 'updated_at']);
                    $newFeeHeads[] = Arr::add($feeHead, 'id', $feeGroup->heads()->firstWhere('uuid', $uuid)->id);
                }
            }

            $transportFee = null;
            if ($this->has_transport_fee) {
                if (! in_array($this->transport_fee, $transportFees->pluck('uuid')->all())) {
                    $validator->errors()->add('transport_fee', trans('validation.exists', ['attribute' => trans('transport.fee.fee')]));
                } else {
                    $transportFee = $transportFees->firstWhere('uuid', $this->transport_fee);
                }
            }

            $lateFee['applicable'] = false;
            if ($this->has_late_fee) {
                if ($this->late_fee_type == 'percent' && $this->late_fee_value > 100) {
                    $validator->errors()->add('late_fee_frequency', trans('validation.exists', ['attribute' => trans('finance.fee_structure.props.late_fee_value')]));
                }

                $lateFee['applicable'] = true;
                $lateFee['type'] = $this->late_fee_type;
                $lateFee['frequency'] = $this->late_fee_frequency;
                $lateFee['value'] = $this->late_fee_value;
            }

            $this->merge([
                'heads' => $newFeeHeads,
                'late_fee' => $lateFee ?? [],
                'fee_group_id' => $feeGroup->id,
                'transport_fee_id' => $transportFee?->id,
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
            'title' => __('finance.fee_structure.props.title'),
            'due_date' => __('finance.fee_structure.props.due_date'),
            'late_fee_type' => __('finance.fee_structure.props.late_fee_type'),
            'late_fee_value' => __('finance.fee_structure.props.late_fee_value'),
            'has_transport_fee' => __('finance.fee_structure.props.has_transport_fee'),
            'has_late_fee' => __('finance.fee_structure.props.has_late_fee'),
            'heads.*.amount' => __('finance.fee_structure.props.amount'),
            'heads.*.is_optional' => __('finance.fee_structure.props.is_optional'),
            'heads.*.applicable_to' => __('finance.fee_structure.props.applicable_to'),
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
            'transport_fee.required_if' => trans('validation.required', ['attribute' => trans('transport.fee.fee')]),
            'late_fee_frequency.required_if' => trans('validation.required', ['attribute' => trans('finance.fee_structure.props.late_fee_frequency')]),
            'late_fee_frequency.in' => trans('validation.exists', ['attribute' => trans('finance.fee_structure.props.late_fee_frequency')]),
            'late_fee_type.in' => trans('validation.exists', ['attribute' => trans('finance.fee_structure.props.late_fee_type')]),
            'late_fee_value.required_if' => trans('validation.required', ['attribute' => trans('finance.fee_structure.props.late_fee_value')]),
        ];
    }
}
