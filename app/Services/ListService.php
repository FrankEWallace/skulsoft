<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ListService
{
    public function preRequisite(Request $request): array
    {
        $countries = Arr::getVar('countries');
        $states = Arr::getVar('states');

        return compact('countries', 'states');
    }
}
