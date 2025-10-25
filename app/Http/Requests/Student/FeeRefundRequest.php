<?php

namespace App\Http\Requests\Student;

use App\Models\Finance\FeeHead;
use App\Models\Finance\Ledger;
use App\Models\Finance\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class FeeRefundRequest extends FormRequest
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
            'date' => 'required|date_format:Y-m-d',
            'ledger' => 'required|uuid',
            'payment_method' => 'required|uuid',
            'records' => 'required|array|min:1',
            'records.*.head' => 'required|uuid|distinct',
            'records.*.amount' => 'required|numeric|min:0.1',
            'instrument_number' => 'nullable|max:20',
            'instrument_date' => 'nullable|date_format:Y-m-d',
            'clearing_date' => 'nullable|date_format:Y-m-d',
            'bank_detail' => 'nullable|min:2|max:100',
            'reference_number' => 'nullable|max:20',
            'remarks' => 'nullable|max:200',
        ];
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

            $feeHeads = FeeHead::query()
                ->byPeriod()
                ->get();

            $total = 0;
            $newRecords = [];
            foreach ($this->records as $index => $record) {
                $feeHead = $feeHeads->firstWhere('uuid', Arr::get($record, 'head'));

                if (! $feeHead) {
                    $validator->errors()->add('records.'.$index.'.head', __('global.could_not_find', ['attribute' => __('finance.fee_head.fee_head')]));
                }

                $total += Arr::get($record, 'amount', 0);

                $newRecords[] = Arr::add($record, 'fee_head_id', $feeHead?->id);
            }

            $this->merge([
                'payment_method_id' => $paymentMethod?->id,
                'payment_method_details' => [
                    'instrument_number' => $this->instrument_number,
                    'instrument_date' => $this->instrument_date,
                    'clearing_date' => $this->clearing_date,
                    'bank_detail' => $this->bank_detail,
                    'reference_number' => $this->reference_number,
                ],
                'ledger' => $ledger,
                'records' => $newRecords,
                'total' => $total,
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
            'date' => __('student.fee_refund.fee_refund.date'),
            'ledger' => __('finance.ledger.ledger'),
            'payment_method' => __('finance.payment_method.payment_method'),
            'records.*.head' => __('finance.fee_head.fee_head'),
            'records.*.amount' => __('student.fee_refund.props.amount'),
            'instrument_number' => __('finance.transaction.props.instrument_number'),
            'instrument_date' => __('finance.transaction.props.instrument_date'),
            'clearing_date' => __('finance.transaction.props.clearing_date'),
            'bank_detail' => __('finance.transaction.props.bank_detail'),
            'reference_number' => __('finance.transaction.props.reference_number'),
            'remarks' => __('student.fee_refund.props.remarks'),
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
