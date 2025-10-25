<?php

namespace App\Services\Finance\Report;

use App\Enums\OptionType;
use App\Enums\Student\StudentStatus;
use App\Http\Resources\Finance\FeeConcessionResource;
use App\Http\Resources\Finance\FeeGroupResource;
use App\Http\Resources\OptionResource;
use App\Models\Finance\FeeConcession;
use App\Models\Finance\FeeGroup;
use App\Models\Option;

class FeeConcessionService
{
    public function preRequisite(): array
    {
        $statuses = StudentStatus::getOptions();

        $feeGroups = FeeGroupResource::collection(FeeGroup::query()
            ->byPeriod()
            ->get());

        $feeConcessions = FeeConcessionResource::collection(FeeConcession::query()
            ->byPeriod()
            ->get());

        $feeConcessionTypes = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::FEE_CONCESSION_TYPE->value)
            ->get());

        return compact('statuses', 'feeGroups', 'feeConcessions', 'feeConcessionTypes');
    }
}
