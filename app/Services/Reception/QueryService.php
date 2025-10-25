<?php

namespace App\Services\Reception;

use App\Enums\Reception\QueryStatus;
use App\Models\Reception\Query;
use Illuminate\Http\Request;

class QueryService
{
    public function preRequisite(Request $request): array
    {
        $statuses = QueryStatus::getOptions();

        return compact('statuses');
    }

    public function deletable(Request $request, Query $query): void {}
}
