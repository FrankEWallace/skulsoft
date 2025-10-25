<?php

namespace App\Services;

use App\Models\Option;
use Illuminate\Http\Request;

class OptionActionService
{
    public function reorder(Request $request): void
    {
        $request->validate(['uuids' => 'array|min:1']);

        foreach ($request->uuids as $order => $uuid) {
            Option::query()
                ->when($request->team, function ($q) {
                    $q->byTeam();
                })
                ->whereType($request->query('type'))
                ->whereUuid($uuid)->update(['meta->position' => $order]);
        }
    }
}
