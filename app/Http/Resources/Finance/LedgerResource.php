<?php

namespace App\Http\Resources\Finance;

use App\Enums\Finance\LedgerGroup;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class LedgerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'alias' => $this->alias,
            'code' => $this->code,
            'type' => new LedgerTypeResource($this->whenLoaded('type')),
            'opening_balance' => $this->opening_balance,
            'current_balance' => $this->current_balance,
            'net_balance' => $this->net_balance,
            'balance' => \Price::from(abs($this->net_balance->value)),
            'balance_color' => $this->getBalanceColor(),
            $this->mergeWhen($this->type->has_code_number, [
                'code_prefix' => $this->code_prefix,
                'code_digit' => $this->code_digit,
                'code_suffix' => $this->code_suffix,
            ]),
            $this->mergeWhen($this->type->has_contact, [
                'contact_number' => $this->contact_number,
                'email' => $this->email,
                'address' => [
                    'address_line1' => Arr::get($this->address, 'address_line1'),
                    'address_line2' => Arr::get($this->address, 'address_line2'),
                    'city' => Arr::get($this->address, 'city'),
                    'state' => Arr::get($this->address, 'state'),
                    'zipcode' => Arr::get($this->address, 'zipcode'),
                    'country' => Arr::get($this->address, 'country'),
                ],
                'address_display' => Arr::toAddress($this->address),
            ]),
            $this->mergeWhen($this->type->has_account, [
                'account' => [
                    'name' => Arr::get($this->account, 'name'),
                    'number' => Arr::get($this->account, 'number'),
                    'bank_name' => Arr::get($this->account, 'bank_name'),
                    'branch_name' => Arr::get($this->account, 'branch_name'),
                    'branch_code' => Arr::get($this->account, 'branch_code'),
                    'branch_address' => Arr::get($this->account, 'branch_address'),
                ],
            ]),
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }

    private function getBalanceColor()
    {
        if ($this->net_balance->value == 0) {
            return;
        }

        if ($this->net_balance->value > 0 && in_array($this->type->type, [
            LedgerGroup::CASH,
            LedgerGroup::BANK_ACCOUNT,
            LedgerGroup::DIRECT_INCOME,
            LedgerGroup::INDIRECT_INCOME,
            LedgerGroup::SUNDRY_DEBTOR,
        ])) {
            return 'success';
        }

        if ($this->net_balance->value < 0 && in_array($this->type->type, [
            LedgerGroup::OVERDRAFT_ACCOUNT,
            LedgerGroup::INDIRECT_EXPENSE,
            LedgerGroup::DIRECT_EXPENSE,
            LedgerGroup::SUNDRY_CREDITOR,
        ])) {
            return 'success';
        }

        return 'danger';
    }
}
