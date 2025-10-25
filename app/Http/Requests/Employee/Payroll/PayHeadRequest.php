<?php

namespace App\Http\Requests\Employee\Payroll;

use App\Enums\Employee\Payroll\PayHeadCategory;
use App\Enums\Employee\Payroll\PayrollVariable;
use App\Models\Employee\Payroll\PayHead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class PayHeadRequest extends FormRequest
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
            'name' => 'required|min:2|max:100',
            'code' => 'required|min:2|max:20|alpha_dash',
            'alias' => 'nullable|min:2|max:100',
            'category' => ['required', new Enum(PayHeadCategory::class)],
            'description' => 'nullable|min:2|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('pay_head.uuid');

            if (in_array($this->code, PayrollVariable::getKeys())) {
                $validator->errors()->add('code', trans('validation.unique', ['attribute' => __('employee.payroll.pay_head.props.code')]));
            }

            $existingNames = PayHead::query()
                ->byTeam()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereName($this->name)
                ->exists();

            if ($existingNames) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => __('employee.payroll.pay_head.props.name')]));
            }

            $existingCodes = PayHead::query()
                ->byTeam()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereCode($this->code)
                ->exists();

            if ($existingCodes) {
                $validator->errors()->add('code', trans('validation.unique', ['attribute' => __('employee.payroll.pay_head.props.code')]));
            }

            if (! $this->alias) {
                return;
            }

            $existingAliases = PayHead::query()
                ->byTeam()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereAlias($this->alias)
                ->exists();

            if ($existingAliases) {
                $validator->errors()->add('alias', trans('validation.unique', ['attribute' => __('employee.payroll.pay_head.props.alias')]));
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
            'name' => __('employee.payroll.pay_head.props.name'),
            'alias' => __('employee.payroll.pay_head.props.alias'),
            'code' => __('employee.payroll.pay_head.props.code'),
            'category' => __('employee.payroll.pay_head.props.category'),
            'description' => __('employee.payroll.pay_head.props.description'),
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
