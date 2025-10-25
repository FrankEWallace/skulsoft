<?php

namespace App\Http\Requests\Finance;

use App\Models\Finance\FeeConcession;
use App\Models\Finance\FeeHead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class FeeConcessionRequest extends FormRequest
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
            'name' => 'required|min:3|max:100',
            'transport_type' => 'required|in:percent,amount',
            'transport_value' => 'required|numeric|min:0',
            'records' => 'array|required|min:1',
            'records.*.head' => 'required|distinct',
            'records.*.type' => 'required|in:percent,amount',
            'records.*.value' => 'required|numeric|min:0',
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
            $uuid = $this->route('fee_concession');

            $existingRecords = FeeConcession::query()
                ->byPeriod()->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })->whereName($this->name)->count();

            if ($existingRecords) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => trans('finance.fee_concession.fee_concession')]));
            }

            if ($this->transport_type == 'percent' && $this->transport_value > 100) {
                $validator->errors()->add('transport_value', trans('validation.lte', ['attribute' => trans('transport.fee.fee'), 'value' => 100]));
            }

            $feeHeads = FeeHead::query()
                ->byPeriod()
                ->select('id', 'uuid')
                ->get();

            $feeHeadUuids = $feeHeads->pluck('uuid')->all();

            $newRecords = [];
            foreach ($this->records as $index => $record) {
                $uuid = Arr::get($record, 'head.uuid');

                $type = Arr::get($record, 'type');
                $value = Arr::get($record, 'value', 0);

                if (! in_array($uuid, $feeHeadUuids)) {
                    $validator->errors()->add('records.'.$index.'.head', trans('validation.exists', ['attribute' => trans('finance.fee_head.fee_head')]));
                } elseif ($type == 'percent' && $value > 100) {
                    $validator->errors()->add('records.'.$index.'.value', trans('validation.lte', ['attribute' => trans('finance.fee_concession.props.value'), 'value' => 100]));
                } else {
                    if ($value > 0) {
                        $newRecords[] = Arr::add($record, 'head.id', $feeHeads->firstWhere('uuid', $uuid)->id);
                    }
                }
            }

            $this->merge(['records' => $newRecords]);
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
            'name' => __('finance.fee_concession.props.name'),
            'transport_type' => __('finance.fee_concession.props.transport_type'),
            'transport_value' => __('finance.fee_concession.props.transport_value'),
            'description' => __('finance.fee_concession.props.description'),
            'records.*.head' => __('finance.fee_head.fee_head'),
            'records.*.value' => __('finance.fee_concession.props.value'),
            'records.*.type' => __('finance.fee_concession.props.type'),
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
