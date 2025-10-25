<?php

namespace App\Http\Requests\Student;

use App\Models\Finance\BankTransfer;
use App\Models\Finance\Ledger;
use App\Models\Finance\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;

class BankTransferActionRequest extends FormRequest
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
            'status' => ['required', 'string', 'in:approved,rejected'],
            'ledger' => ['required_if:status,approved', 'uuid'],
            'payment_method' => ['required_if:status,approved', 'uuid'],
            'instrument_number' => 'nullable|max:20',
            'instrument_date' => 'nullable|date_format:Y-m-d',
            'clearing_date' => 'nullable|date_format:Y-m-d',
            'bank_detail' => 'nullable|min:2|max:100',
            'reference_number' => 'nullable|max:20',
            'comment' => 'required|max:255',
        ];

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $mediaModel = (new BankTransfer)->getModelName();

            $studentUuid = $this->route('student');

            $paymentMethod = $this->status == 'approved' ? PaymentMethod::query()
                ->byTeam()
                ->where('is_payment_gateway', false)
                ->whereUuid($this->payment_method)
                ->getOrFail(trans('finance.payment_method.payment_method'), 'payment_method') : null;

            $ledger = $this->status == 'approved' ? Ledger::query()
                ->byTeam()
                ->subType('primary')
                ->whereUuid($this->ledger)
                ->getOrFail(trans('finance.ledger.ledger'), 'ledger') : null;

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
            'status' => __('finance.bank_transfer.props.status'),
            'ledger' => __('finance.ledger.ledger'),
            'payment_method' => __('finance.payment_method.payment_method'),
            'instrument_number' => __('finance.transaction.props.instrument_number'),
            'instrument_date' => __('finance.transaction.props.instrument_date'),
            'clearing_date' => __('finance.transaction.props.clearing_date'),
            'bank_detail' => __('finance.transaction.props.bank_detail'),
            'reference_number' => __('finance.transaction.props.reference_number'),
            'comment' => __('finance.bank_transfer.props.comment'),
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
            'ledger.required_if' => __('validation.required', ['attribute' => __('finance.ledger.ledger')]),
            'payment_method.required_if' => __('validation.required', ['attribute' => __('finance.payment_method.payment_method')]),
        ];
    }
}
