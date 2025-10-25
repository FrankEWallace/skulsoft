<?php

namespace App\Http\Requests\Finance;

use App\Concerns\SimpleValidation;
use App\Enums\Finance\TransactionType;
use App\Enums\OptionType;
use App\Models\Finance\Ledger;
use App\Models\Finance\PaymentMethod;
use App\Models\Option;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Enum;

class TransactionRequest extends FormRequest
{
    use SimpleValidation;

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
            'type' => ['required', new Enum(TransactionType::class)],
            'primary_ledger.uuid' => 'required|uuid',
            'date' => 'required|date_format:Y-m-d',
            'category' => 'nullable|uuid',
            'records' => 'required|array|min:1',
            'records.*.secondary_ledger.uuid' => 'required|uuid|distinct',
            'records.*.amount' => 'required|numeric|min:0.01',
            'records.*.remarks' => 'nullable|max:255',
            'payment_method' => 'required|uuid',
            'instrument_number' => 'nullable|max:20',
            'instrument_date' => 'nullable|date_format:Y-m-d',
            'clearing_date' => 'nullable|date_format:Y-m-d',
            'bank_detail' => 'nullable|min:2|max:100',
            'reference_number' => 'nullable|max:20',
            'description' => 'nullable|min:2|max:1000',
            'remarks' => 'nullable|min:2|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('transaction.uuid');

            $category = $this->category ? Option::query()
                ->byTeam()
                ->where('type', OptionType::TRANSACTION_CATEGORY)
                ->whereUuid($this->category)
                ->getOrFail(trans('finance.transaction.category.category'), 'category') : null;

            $paymentMethod = PaymentMethod::query()
                ->byTeam()
                ->where('is_payment_gateway', false)
                ->whereUuid($this->payment_method)
                ->getOrFail(trans('finance.payment_method.payment_method'), 'payment_method');

            $primaryLedger = Ledger::query()
                ->byTeam()
                ->subType('primary')
                ->whereUuid($this->input('primary_ledger.uuid'))
                ->getOrFail(trans('finance.ledger.ledger'), 'primary_ledger');

            $secondaryLedgerSubType = $this->type == 'transfer' ? 'primary' : 'secondary';

            $secondaryLedgers = Ledger::query()
                ->select('id', 'uuid')
                ->byTeam()
                ->subType($secondaryLedgerSubType)
                ->get();

            $newRecords = [];
            foreach ($this->records as $index => $record) {
                $secondaryLedgerUuid = Arr::get($record, 'secondary_ledger.uuid');

                if ($primaryLedger->uuid == $secondaryLedgerUuid) {
                    $validator->errors()->add('records.'.$index.'.secondary_ledger.uuid', trans('validation.different', ['attribute' => __('finance.ledger.ledger'), 'other' => __('finance.ledger.secondary_ledger')]));
                }

                $secondaryLedger = $secondaryLedgers->firstWhere('uuid', $secondaryLedgerUuid);

                if (! $secondaryLedger) {
                    $validator->errors()->add('records.'.$index.'.secondary_ledger.uuid', trans('global.could_not_find', ['attribute' => __('finance.ledger.ledger')]));
                }

                $newRecords[] = [
                    'secondary_ledger_id' => $secondaryLedger?->id,
                    'amount' => Arr::get($record, 'amount'),
                ];
            }

            $paymentMethods[] = [
                'payment_method_id' => $paymentMethod?->id,
                'amount' => collect($this->records)->sum('amount'),
                'details' => [
                    'instrument_number' => $this->instrument_number,
                    'instrument_date' => $this->instrument_date,
                    'clearing_date' => $this->clearing_date,
                    'bank_detail' => $this->bank_detail,
                    'reference_number' => $this->reference_number,
                ],
            ];

            $this->merge([
                'category_id' => $category?->id,
                'payment_methods' => $paymentMethods,
                'payment_method_code' => $paymentMethod->code,
                'primary_ledger' => $primaryLedger,
                'ledger_code' => $primaryLedger?->code,
                'records' => $newRecords,
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
            'type' => __('finance.transaction.props.type'),
            'primary_ledger.uuid' => __('finance.ledger.ledger'),
            'date' => __('finance.transaction.props.date'),
            'category' => __('finance.transaction.category.category'),
            'records.*.secondary_ledger.uuid' => __('finance.ledger.ledger'),
            'records.*.amount' => __('finance.transaction.props.amount'),
            'payment_method' => __('finance.payment_method.payment_method'),
            'instrument_number' => __('finance.transaction.props.instrument_number'),
            'instrument_date' => __('finance.transaction.props.instrument_date'),
            'clearing_date' => __('finance.transaction.props.clearing_date'),
            'bank_detail' => __('finance.transaction.props.bank_detail'),
            'reference_number' => __('finance.transaction.props.reference_number'),
            'description' => __('finance.transaction.props.description'),
            'remarks' => __('finance.transaction.props.remarks'),
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
