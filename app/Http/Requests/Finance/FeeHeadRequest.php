<?php

namespace App\Http\Requests\Finance;

use App\Actions\Finance\CreateCustomFeeHead;
use App\Enums\Finance\DefaultCustomFeeType;
use App\Models\Finance\FeeGroup;
use App\Models\Finance\FeeHead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class FeeHeadRequest extends FormRequest
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
            'name' => ['required', 'min:2', 'max:100'],
            'fee_group' => 'nullable|uuid',
            'type' => ['nullable', new Enum(DefaultCustomFeeType::class)],
            'description' => 'nullable|min:2|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('fee_head');

            if (in_array(strtolower($this->name), ['transport fee', 'late fee', 'additional charge', 'additional discount', 'registration fee'])) {
                $validator->errors()->add('name', trans('finance.fee_head.reserved_name'));
            }

            $feeGroup = $this->fee_group ? FeeGroup::query()
                ->byPeriod()
                ->where('uuid', $this->fee_group)
                ->getOrFail(trans('finance.fee_group.fee_group'), 'fee_group') : (new CreateCustomFeeHead)->execute();

            $existingRecords = FeeHead::query()
                ->when($this->fee_group, function ($q) use ($feeGroup) {
                    $q->whereFeeGroupId($feeGroup->id);
                })
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->wherePeriodId(auth()->user()->current_period_id)
                ->whereName($this->name)
                ->exists();

            if ($existingRecords) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => __('finance.fee_head.props.name')]));
            }

            if ((! $this->fee_group || $feeGroup?->is_custom) && $this->type) {
                $existingFeeType = FeeHead::query()
                    ->where('type', $this->type)
                    ->wherePeriodId(auth()->user()->current_period_id)
                    ->when($uuid, function ($q, $uuid) {
                        $q->where('uuid', '!=', $uuid);
                    })
                    ->exists();

                if ($existingFeeType) {
                    $validator->errors()->add('type', trans('validation.unique', ['attribute' => __('finance.fee_head.props.type')]));
                }
            } else {
                $this->merge(['type' => null]);
            }

            $this->merge(['fee_group_id' => $feeGroup?->id]);
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
            'name' => __('finance.fee_head.props.name'),
            'fee_group' => __('finance.fee_group.fee_group'),
            'type' => __('finance.fee_head.props.type'),
            'description' => __('finance.fee_head.props.description'),
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
