<?php

namespace App\Services\Misc;

use App\Models\Qualification;

class InstituteService
{
    public function searchInstitute(string $query)
    {
        return Qualification::query()
            ->where('institute', 'like', '%'.$query.'%')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return $item->institute;
            });
    }

    public function searchAffiliationBody(string $query)
    {
        return Qualification::query()
            ->where('affiliated_to', 'like', '%'.$query.'%')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return $item->affiliated_to;
            });
    }
}
