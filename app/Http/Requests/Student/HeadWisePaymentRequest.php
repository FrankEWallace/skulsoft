<?php

namespace App\Http\Requests\Student;

use App\Models\Finance\FeeGroup;
use App\Models\Finance\Ledger;
use App\Models\Finance\PaymentMethod;
use App\Models\Finance\Transaction;
use App\Models\Student\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HeadWisePaymentRequest extends FormRequest
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
            'transport_fee' => 'nullable|numeric|min:0',
            'amount' => 'required|numeric|min:0.01',
            'fee_group' => 'required|uuid',
            'heads' => 'array',
            'heads.*.uuid' => 'required|uuid',
            'heads.*.amount' => 'required|numeric|min:0',
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

            $student = Student::query()
                ->select('id', 'period_id')
                ->byTeam()
                ->whereUuid($studentUuid)
                ->firstOrFail();

            $feeGroup = FeeGroup::query()
                ->with('heads')
                ->byPeriod($student->period_id)
                ->where('uuid', $this->fee_group)
                ->getOrFail(trans('finance.fee_group.fee_group'));

            if ($feeGroup->getMeta('is_custom') && $this->transport_fee) {
                throw ValidationException::withMessages(['transport_fee' => trans('global.could_not_find', ['attribute' => trans('finance.fee_default_fee_heads.transport_fee')])]);
            }

            if ($feeGroup->getMeta('is_custom') && $this->late_fee) {
                throw ValidationException::withMessages(['late_fee' => trans('global.could_not_find', ['attribute' => trans('finance.fee.default_fee_heads.late_fee')])]);
            }

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

            $newFeeHeads = [];

            $total = 0;
            foreach ($this->heads as $index => $feeHead) {
                $feeHeadUuid = Arr::get($feeHead, 'uuid');
                $feeHeadAmount = Arr::get($feeHead, 'amount') * 1;

                $feeHead = $feeGroup->heads->firstWhere('uuid', $feeHeadUuid);

                if (! $feeHead) {
                    throw ValidationException::withMessages(['fee_heads.'.$index.'.amount' => trans('global.could_not_find', ['attribute' => trans('finance.fee_head.fee_head')])]);
                }

                if ($feeHeadAmount > 0) {
                    $total += $feeHeadAmount;
                    $newFeeHeads[$index] = [
                        'id' => $feeHead->id,
                        'uuid' => $feeHeadUuid,
                        'amount' => $feeHeadAmount,
                    ];
                }
            }

            // if (! count($newFeeHeads)) {
            //     throw ValidationException::withMessages(['message' => trans('student.fee.no_payable_fee')]);
            // }

            $lateFee = ! empty($this->late_fee) ? $this->late_fee : 0;
            $transportFee = ! empty($this->transport_fee) ? $this->transport_fee : 0;

            if (count($this->heads) == 0 && $transportFee == 0) {
                throw ValidationException::withMessages(['message' => trans('student.fee.no_payable_fee')]);
            }

            $total += $lateFee;
            $total += $transportFee;

            $newAdditionalCharges = [];
            foreach ($this->additional_charges as $index => $additionalCharge) {
                $label = Arr::get($additionalCharge, 'label');
                $amount = Arr::get($additionalCharge, 'amount');

                if ($amount > 0 && empty($label)) {
                    $validator->errors()->add('additional_charges.'.$index.'.label', trans('validation.required', ['attribute' => __('student.fee.props.additional_charge_label')]));
                }

                if ($amount) {
                    $total += $amount;
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
                    $total -= $amount;
                    $newAdditionalDiscounts[] = [
                        'label' => Str::of($label)->title()->value,
                        'amount' => $amount,
                    ];
                }
            }

            if ($total != $this->amount) {
                throw ValidationException::withMessages(['amount' => trans('finance.payment_amount_mismatch')]);
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
                'ledger_id' => $ledger->id,
                'additional_charges' => $newAdditionalCharges,
                'additional_discounts' => $newAdditionalDiscounts,
                'fee_group_id' => $feeGroup->id,
                'heads' => $newFeeHeads,
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
            'transport_fee' => __('finance.fee.default_fee_heads.transport_fee'),
            'amount' => __('student.fee.props.amount'),
            'heads.*.uuid' => __('finance.fee_head.fee_head'),
            'heads.*.amount' => __('student.fee.props.amount'),
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
