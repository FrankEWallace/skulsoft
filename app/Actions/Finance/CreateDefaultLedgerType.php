<?php

namespace App\Actions\Finance;

use App\Enums\Finance\LedgerGroup;
use App\Models\Finance\LedgerType;

class CreateDefaultLedgerType
{
    public function execute(int $teamId): void
    {
        foreach (LedgerGroup::getKeys() as $ledgerGroupKey) {
            $ledgerGroup = LedgerGroup::tryFrom($ledgerGroupKey);

            $ledgerType = LedgerType::firstOrCreate([
                'type' => $ledgerGroup->value,
                'team_id' => $teamId,
            ]);

            $ledgerType->name = trans('finance.ledger.groups.'.$ledgerGroup->value);
            $ledgerType->is_default = 1;
            $ledgerType->save();
        }
    }
}
