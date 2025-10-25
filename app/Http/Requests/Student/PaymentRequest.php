<?php

namespace App\Http\Requests\Student;

use App\Models\Finance\Ledger;
use App\Models\Finance\PaymentMethod;
use App\Models\Finance\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentRequest extends FormRequest
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
            'code_number' => 'nullable|max:50',
            'date' => 'required|date_format:Y-m-d',
            'late_fee' => 'required|numeric|min:0',
            'amount' => 'required|numeric|min:0',
            'additional_charges' => 'array',
            'additional_charges.*.label' => 'nullable|max:100|distinct',
            'additional_charges.*.amount' => 'required|numeric|min:0',
            'additional_discounts' => 'array',
            'additional_discounts.*.label' => 'nullable|max:100|distinct',
            'additional_discounts.*.amount' => 'required|numeric|min:0',
            'ledger' => 'required|uuid',
            'payment_method' => 'required|uuid',
            'instrument_number' => 'nullable|max:20',
            'instrument_date' => 'nullable|date_format:Y-m-d',
            'clearing_date' => 'nullable|date_format:Y-m-d',
            'bank_detail' => 'nullable|min:2|max:100',
            'reference_number' => 'nullable|max:20',
            'remarks' => 'nullable|max:255',
        ];

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $studentUuid = $this->route('student');

            $paymentMethod = PaymentMethod::query()
                ->byTeam()
                ->where('is_payment_gateway', false)
                ->whereUuid($this->payment_method)
                ->getOrFail(trans('finance.payment_method.payment_method'), 'payment_method');

            $ledger = Ledger::query()
                ->byTeam()
                ->subType('primary')
                ->whereUuid($this->ledger)
                ->getOrFail(trans('finance.ledger.ledger'), 'ledger');

            if ($this->code_number) {
                $existingCodeNumber = Transaction::query()
                    ->join('periods', 'periods.id', '=', 'transactions.period_id')
                    ->where('periods.team_id', auth()->user()->current_team_id)
                    ->where('transactions.code_number', $this->code_number)
                    ->exists();

                if ($existingCodeNumber) {
                    throw ValidationException::withMessages(['code_number' => trans('global.duplicate', ['attribute' => trans('finance.transaction.props.code_number')])]);
                }
            }

            if ($this->date != today()->toDateString() && ! auth()->user()->can('fee:change-payment-date')) {
                throw ValidationException::withMessages(['message' => trans('student.fee.could_not_change_payment_date')]);
            }

            $newAdditionalCharges = [];
            foreach ($this->additional_charges as $index => $additionalCharge) {
                $label = Arr::get($additionalCharge, 'label');
                $amount = Arr::get($additionalCharge, 'amount');

                if ($amount > 0 && empty($label)) {
                    $validator->errors()->add('additional_charges.'.$index.'.label', trans('validation.required', ['attribute' => __('student.fee.props.additional_charge_label')]));
                }

                if ($amount) {
                    $newAdditionalCharges[] = [
                        'label' => Str::of($label)->title()->value,
                        'amount' => $amount,
                    ];
                }
            }

            $newAdditionalDiscounts = [];
            foreach ($this->additional_discounts as $index => $additionalDiscount) {
                $label = Arr::get($additionalDiscount, 'label');
                $amount = Arr::get($additionalDiscount, 'amount');

                if ($amount > 0 && empty($label)) {
                    $validator->errors()->add('additional_discounts.'.$index.'.label', trans('validation.required', ['attribute' => __('student.fee.props.additional_discount_label')]));
                }

                if ($amount) {
                    $newAdditionalDiscounts[] = [
                        'label' => Str::of($label)->title()->value,
                        'amount' => $amount,
                    ];
                }
            }

            $this->merge([
                'ledger_code' => $ledger?->code,
                'payment_method_code' => $paymentMethod?->code,
                'payment_method_id' => $paymentMethod?->id,
                'payment_method_details' => [
                    'instrument_number' => $this->instrument_number,
                    'instrument_date' => $this->instrument_date,
                    'clearing_date' => $this->clearing_date,
                    'bank_detail' => $this->bank_detail,
                    'reference_number' => $this->reference_number,
                ],
                'ledger' => $ledger,
                'additional_charges' => $newAdditionalCharges,
                'additional_discounts' => $newAdditionalDiscounts,
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
            'code_number' => __('finance.transaction.props.code_number'),
            'date' => __('student.fee.props.date'),
            'late_fee' => __('finance.fee.default_fee_heads.late_fee'),
            'amount' => __('student.fee.props.amount'),
            'additional_charges' => __('student.fee.props.additional_charge'),
            'additional_charges.*.label' => __('student.fee.props.additional_charge_label'),
            'additional_charges.*.amount' => __('student.fee.props.additional_charge_amount'),
            'additional_discounts' => __('student.fee.props.additional_discount'),
            'additional_discounts.*.label' => __('student.fee.props.additional_discount_label'),
            'additional_discounts.*.amount' => __('student.fee.props.additional_discount_amount'),
            'ledger' => __('finance.ledger.ledger'),
            'payment_method' => __('finance.payment_method.payment_method'),
            'instrument_number' => __('finance.transaction.props.instrument_number'),
            'instrument_date' => __('finance.transaction.props.instrument_date'),
            'clearing_date' => __('finance.transaction.props.clearing_date'),
            'bank_detail' => __('finance.transaction.props.bank_detail'),
            'reference_number' => __('finance.transaction.props.reference_number'),
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
