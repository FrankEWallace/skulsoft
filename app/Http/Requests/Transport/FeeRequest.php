<?php

namespace App\Http\Requests\Transport;

use App\Models\Transport\Circle;
use App\Models\Transport\Fee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class FeeRequest extends FormRequest
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
            'records' => 'array|required|min:1',
            'records.*.circle' => 'required|distinct',
            'records.*.arrival_amount' => 'required|numeric|min:0',
            'records.*.departure_amount' => 'required|numeric|min:0',
            'records.*.roundtrip_amount' => 'required|numeric|min:0',
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
            $uuid = $this->route('fee');

            $existingRecords = Fee::query()
                ->byPeriod()->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })->whereName($this->name)->count();

            if ($existingRecords) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => trans('transport.fee.fee')]));
            }

            $circles = Circle::query()
                ->byPeriod()
                ->select('id', 'uuid')
                ->get();

            $circleUuids = $circles->pluck('uuid')->all();

            $newRecords = [];
            foreach ($this->records as $index => $record) {
                $uuid = Arr::get($record, 'circle.uuid');
                if (! in_array($uuid, $circleUuids)) {
                    $validator->errors()->add('records.'.$index.'.circle', trans('validation.exists', ['attribute' => trans('transport.circle.circle')]));
                } else {
                    $newRecords[] = Arr::add($record, 'circle.id', $circles->firstWhere('uuid', $uuid)->id);
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
            'name' => __('transport.fee.props.name'),
            'description' => __('transport.fee.props.description'),
            'records.*.circle' => __('transport.circle.circle'),
            'records.*.arrival_amount' => __('transport.fee.props.arrival_amount'),
            'records.*.departure_amount' => __('transport.fee.props.departure_amount'),
            'records.*.roundtrip_amount' => __('transport.fee.props.roundtrip_amount'),
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
