<?php

namespace App\Services\Library;

use App\Enums\Library\HoldStatus;
use App\Enums\OptionType;
use App\Http\Resources\OptionResource;
use App\Models\Option;

class BookCopyService
{
    public function preRequisite()
    {
        $conditions = OptionResource::collection(Option::query()
            ->byTeam()
            ->where('type', OptionType::BOOK_CONDITION)
            ->get());

        $statuses = [
            [
                'label' => trans('library.book_copy.statuses.hold'),
                'value' => 'hold',
            ],
            [
                'label' => trans('library.book_copy.statuses.stock'),
                'value' => 'stock',
            ],
        ];

        $holdStatuses = HoldStatus::getOptions();

        return compact('conditions', 'statuses', 'holdStatuses');
    }
}
