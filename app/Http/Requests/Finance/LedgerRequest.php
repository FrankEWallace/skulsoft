<?php

namespace App\Http\Requests\Finance;

use App\Concerns\SimpleValidation;
use App\Models\Finance\Ledger;
use App\Models\Finance\LedgerType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class LedgerRequest extends FormRequest
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
        $rules = [
            'name' => 'required|min:2|max:100',
            'alias' => 'nullable|min:2|max:50',
            'opening_balance' => 'required|numeric',
            'description' => 'nullable|max:1000',
            'code' => ['nullable', 'min:2', 'max:100'],
            'type' => 'required',
            // 'code_prefix' => 'nullable|min:1|max:200',
            // 'code_digit' => 'nullable|integer|min:1|max:10',
            // 'code_suffix' => 'nullable|min:1|max:200',
            'address.address_line1' => 'nullable|min:2|max:100',
            'address.address_line2' => 'nullable|min:2|max:100',
            'address.city' => 'nullable|min:2|max:100',
            'address.state' => 'nullable|min:2|max:100',
            'address.zipcode' => 'nullable|min:2|max:100',
            'address.country' => 'nullable|min:2|max:100',
            'account.name' => 'nullable|min:2|max:100',
            'account.number' => 'nullable|min:2|max:100',
            'account.bank_name' => 'nullable|min:2|max:100',
            'account.branch_name' => 'nullable|min:2|max:100',
            'account.branch_address' => 'nullable|min:2|max:100',
        ];

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {

            $validator->after(function ($validator) {
                $this->change($validator, 'address.address_line1', 'address_line1');
                $this->change($validator, 'address.address_line2', 'address_line2');
                $this->change($validator, 'address.city', 'city');
                $this->change($validator, 'address.state', 'state');
                $this->change($validator, 'address.zipcode', 'zipcode');
                $this->change($validator, 'address.country', 'country');
                $this->change($validator, 'account.name', 'accountName');
                $this->change($validator, 'account.number', 'accountNumber');
                $this->change($validator, 'account.bankName', 'accountBankName');
                $this->change($validator, 'account.branchName', 'accountBranchName');
                $this->change($validator, 'account.branchAddress', 'accountBranchAddress');
            });

            return;
        }

        $validator->after(function ($validator) {

            $uuid = null;
            if ($this->route('ledger')) {
                $uuid = $this->route('ledger.uuid');
            } elseif ($this->route('vendor')) {
                $uuid = $this->route('vendor.uuid');
            }

            if (Str::isUuid($this->type)) {
                $ledgerType = LedgerType::findByUuidOrFail($this->type, 'type');
            } else {
                $ledgerType = LedgerType::findByTypeOrFail($this->type, 'type');
            }

            $existingLedgers = Ledger::query()
                ->byTeam()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->whereName($this->name)
                ->exists();

            if ($existingLedgers) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => trans('finance.ledger.ledger')]));
            }

            if ($this->code) {
                $existingLedgers = Ledger::query()
                    ->when($uuid, function ($q, $uuid) {
                        $q->where('uuid', '!=', $uuid);
                    })
                    ->byTeam()
                    ->where('config->code', $this->code)
                    ->exists();

                if ($existingLedgers) {
                    $validator->errors()->add('code', trans('validation.unique', ['attribute' => trans('finance.ledger.props.code')]));
                }
            }

            $this->whenFilled('alias', function (string $input) use ($validator, $uuid) {
                $existingLedgerAliases = Ledger::query()
                    ->byTeam()
                    ->when($uuid, function ($q, $uuid) {
                        $q->where('uuid', '!=', $uuid);
                    })
                    ->whereAlias($input)
                    ->exists();

                if ($existingLedgerAliases) {
                    $validator->errors()->add('alias', trans('validation.unique', ['attribute' => trans('finance.ledger.ledger')]));
                }
            });

            $this->merge([
                'ledger_type' => $ledgerType,
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
            'name' => __('finance.ledger.props.name'),
            'alias' => __('finance.ledger.props.alias'),
            'code' => __('finance.ledger.props.code'),
            'opening_balance' => __('finance.ledger.props.opening_balance'),
            'type' => __('finance.ledger_type.ledger_type'),
            'description' => __('finance.ledger.props.description'),
            'address.address_line1' => __('contact.props.address.address_line1'),
            'address.address_line2' => __('contact.props.address.address_line2'),
            'address.city' => __('contact.props.address.city'),
            'address.state' => __('contact.props.address.state'),
            'address.zipcode' => __('contact.props.address.zipcode'),
            'address.country' => __('contact.props.address.country'),
            'account.name' => __('finance.account.props.name'),
            'account.number' => __('finance.account.props.number'),
            'account.bank_name' => __('finance.account.props.bank_name'),
            'account.branch_name' => __('finance.account.props.branch_name'),
            'account.branch_address' => __('finance.account.props.branch_address'),
            'account.branch_code' => __('finance.account.props.branch_code'),
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
