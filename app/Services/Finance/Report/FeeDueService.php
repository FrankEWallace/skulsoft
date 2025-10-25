<?php

namespace App\Services\Finance\Report;

use App\Enums\Student\StudentStatus;
use App\Http\Resources\Finance\FeeGroupResource;
use App\Models\Finance\FeeGroup;

class FeeDueService
{
    public function preRequisite(): array
    {
        $statuses = StudentStatus::getOptions();

        $feeGroups = FeeGroupResource::collection(FeeGroup::query()
            ->byPeriod()
            ->get());

        return compact('statuses', 'feeGroups');
    }
}
