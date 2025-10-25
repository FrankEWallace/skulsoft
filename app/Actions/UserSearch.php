<?php

namespace App\Actions;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;

class UserSearch
{
    public function execute(Request $request, ?Team $team = null)
    {
        if (strlen($request->q) < 1) {
            return [];
        }

        $query = User::query();

        if ($team) {
            $query->whereHas('roles', function ($q) use ($team) {
                $q->where('model_has_roles.team_id', $team->id);
            });
        }

        return app(Pipeline::class)
            ->send($query)
            ->through([
                'App\QueryFilters\LikeMatch:q,name,email,username',
            ])->thenReturn()
            ->orderBy('name', 'asc')
            ->take(5)
            ->get();
    }
}
