<?php

namespace App\Http\Requests\Student;

use App\Enums\Finance\PaymentStatus;
use App\Enums\Student\RegistrationStatus;
use App\Models\Finance\Ledger;
use App\Models\Finance\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class RegistrationPaymentRequest extends FormRequest
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
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date_format:Y-m-d',
            'ledger.uuid' => 'required|uuid',
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
            $registration = $this->route('registration');

            $paymentMethod = PaymentMethod::query()
                ->byTeam()
                ->where('is_payment_gateway', false)
                ->whereUuid($this->payment_method)
                ->getOrFail(trans('finance.payment_method.payment_method'), 'payment_method');

            $ledger = Ledger::query()
                ->byTeam()
                ->subType('primary')
                ->whereUuid($this->input('ledger.uuid'))
                ->getOrFail(trans('finance.ledger.ledger'), 'ledger');

            if ($registration->status != RegistrationStatus::PENDING) {
                throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
            }

            if ($registration->fee->value == 0) {
                throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
            }

            if (! auth()->user()->can('registration:action') && $this->amount != $registration->fee->value) {
                throw ValidationException::withMessages(['message' => trans('student.registration.could_not_edit_registration_fee')]);
            }

            if ($this->amount > $registration->fee->value) {
                throw ValidationException::withMessages(['message' => trans('finance.fee.amount_gt_balance', ['amount' => \Price::from($this->amount)->formatted, 'balance' => $registration->fee->formatted])]);
            }

            if (! in_array($registration->payment_status, [PaymentStatus::UNPAID, PaymentStatus::PARTIALLY_PAID])) {
                throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
            }

            if ($this->date < $registration->date->value) {
                $validator->errors()->add('date', trans('validation.after_or_equal', ['attribute' => trans('student.registration.props.payment_date'), 'date' => $registration->date->formatted]));
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
            'amount' => __('academic.course.props.registration_fee'),
            'date' => __('student.registration.props.payment_date'),
            'ledger.uuid' => __('finance.ledger.ledger'),
            'payment_method' => __('finance.payment_method.payment_method'),
            'instrument_number' => __('finance.transaction.props.instrument_number'),
            'instrument_date' => __('finance.transaction.props.instrument_date'),
            'clearing_date' => __('finance.transaction.props.clearing_date'),
            'bank_detail' => __('finance.transaction.props.bank_detail'),
            'reference_number' => __('finance.transaction.props.reference_number'),
            'remarks' => __('student.registration.props.payment_remarks'),
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
            //
        ];
    }
}
