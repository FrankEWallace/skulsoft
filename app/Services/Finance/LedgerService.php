<?php

namespace App\Services\Finance;

use App\Http\Resources\Finance\LedgerTypeResource;
use App\Models\Finance\Ledger;
use App\Models\Finance\LedgerType;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LedgerService
{
    public function preRequisite(): array
    {
        $ledgerTypes = LedgerTypeResource::collection(LedgerType::query()
            ->byTeam()
            ->get());

        return compact('ledgerTypes');
    }

    public function create(Request $request): Ledger
    {
        \DB::beginTransaction();

        $ledger = Ledger::forceCreate($this->formatParams($request));

        \DB::commit();

        return $ledger;
    }

    private function formatParams(Request $request, ?Ledger $ledger = null): array
    {
        $ledgerType = $request->ledger_type;

        $formatted = [
            'name' => $request->name,
            'alias' => $request->alias,
            'ledger_type_id' => $ledgerType->id,
            'opening_balance' => $request->opening_balance,
            'description' => $request->description,
        ];

        // if ($ledgerType->hasCodeNumber) {
        //     $formatted['code_prefix'] = $request->code_prefix;
        //     $formatted['code_digit'] = $request->code_digit;
        //     $formatted['code_suffix'] = $request->code_suffix;
        // } else {
        //     $formatted['code_prefix'] = null;
        //     $formatted['code_digit'] = null;
        //     $formatted['code_suffix'] = null;
        // }

        if ($ledgerType->has_contact) {
            $formatted['contact_number'] = $request->contact_number;
            $formatted['email'] = $request->email;
            $formatted['address']['address_line1'] = $request->input('address.address_line1');
            $formatted['address']['address_line2'] = $request->input('address.address_line2');
            $formatted['address']['city'] = $request->input('address.city');
            $formatted['address']['state'] = $request->input('address.state');
            $formatted['address']['zipcode'] = $request->input('address.zipcode');
            $formatted['address']['country'] = $request->input('address.country');
        } else {
            $formatted['contact_number'] = null;
            $formatted['email'] = null;
            $formatted['address'] = null;
        }

        if ($ledgerType->has_account) {
            $formatted['account']['name'] = $request->input('account.name');
            $formatted['account']['number'] = $request->input('account.number');
            $formatted['account']['bank_name'] = $request->input('account.bank_name');
            $formatted['account']['branch_name'] = $request->input('account.branch_name');
            $formatted['account']['branch_code'] = $request->input('account.branch_code');
            $formatted['account']['branch_address'] = $request->input('account.branch_address');
        } else {
            $formatted['account'] = null;
        }

        $config = $ledger?->config ?? [];

        $config['code'] = $request->code;

        $formatted['config'] = $config;

        return $formatted;
    }

    public function isEditable(Ledger $ledger) {}

    public function update(Ledger|Vendor $ledger, Request $request): void
    {
        $this->isEditable($ledger);

        \DB::beginTransaction();

        $ledger->forceFill($this->formatParams($request, $ledger))->save();

        \DB::commit();
    }

    public function deletable(Ledger|Vendor $ledger): bool
    {
        $this->isEditable($ledger);

        // $parentExists = \DB::table('ledgers')
        //     ->where('parent_id', $ledger->id)
        //     ->exists();

        // if ($parentExists) {
        //     throw ValidationException::withMessages(['message' => trans('global.associated_with_parent_dependency', ['attribute' => trans('finance.ledger.ledger')])]);
        // }

        $transactionExists = \DB::table('transactions')
            ->where('ledger_id', $ledger->id)
            ->exists();

        if ($transactionExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('finance.ledger.ledger'), 'dependency' => trans('finance.transaction.transaction')])]);
        }

        $transactionRecordExists = \DB::table('transaction_records')
            ->where('ledger_id', $ledger->id)
            ->exists();

        if ($transactionRecordExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('finance.ledger.ledger'), 'dependency' => trans('finance.transaction.transaction')])]);
        }

        return true;
    }
}
