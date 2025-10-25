<?php

namespace App\Services\Misc;

use App\Enums\OptionType;
use App\Models\Option;

class UnitService
{
    public function searchUnit(string $query = null)
    {
        $units = Option::query()
            ->where('type', OptionType::UNIT)
            ->when($query, function ($q) use ($query) {
                return $q->where('name', 'like', '%'.$query.'%');
            })
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return $item->name;
            });

        return $units;
    }
}
