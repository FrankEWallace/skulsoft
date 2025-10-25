<?php

namespace App\Services\Team;

use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Spatie\Permission\Models\Permission;

class PermissionSearchService
{
    public function search(Request $request): array
    {
        if (strlen($request->q) < 1) {
            return [];
        }

        $query = Permission::query();

        return app(Pipeline::class)
            ->send($query)
            ->through([
                'App\QueryFilters\LikeMatch:q,name',
            ])->thenReturn()
            ->orderBy('name', 'asc')
            ->take(5)
            ->get()
            ->pluck('name')
            ->all();
    }
}
