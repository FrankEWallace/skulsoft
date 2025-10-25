<?php

namespace App\Services\Finance\Report;

use App\Enums\Finance\BankTransferStatus;

class BankTransferService
{
    public function preRequisite(): array
    {
        $statuses = BankTransferStatus::getOptions();

        return compact('statuses');
    }
}
