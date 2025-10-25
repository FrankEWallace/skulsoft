<?php

namespace App\Http\Requests\Finance;

use App\Models\Finance\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;

class PaymentMethodRequest extends FormRequest
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
            'code' => ['required', 'min:1', 'max:100'],
            'is_payment_gateway' => 'boolean',
            'payment_gateway_name' => 'required_if:is_payment_gateway,true|min:2|max:50',
            'description' => 'nullable|min:2|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('payment_method.uuid');

            $existingRecords = PaymentMethod::query()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereTeamId(auth()->user()->current_team_id)
                ->whereName($this->name)
                ->exists();

            if ($existingRecords) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => trans('finance.payment_method.props.name')]));
            }

            $existingRecords = PaymentMethod::query()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereTeamId(auth()->user()->current_team_id)
                ->where('config->code', $this->code)
                ->exists();

            if ($existingRecords) {
                $validator->errors()->add('code', trans('validation.unique', ['attribute' => trans('finance.payment_method.props.code')]));
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
            'name' => __('finance.payment_method.props.name'),
            'code' => __('finance.payment_method.props.code'),
            'is_payment_gateway' => __('finance.payment_method.props.is_payment_gateway'),
            'payment_gateway_name' => __('finance.payment_method.props.payment_gateway_name'),
            'description' => __('finance.payment_method.props.description'),
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
