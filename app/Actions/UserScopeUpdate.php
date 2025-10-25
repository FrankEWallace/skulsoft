<?php

namespace App\Actions;

use App\Enums\UserScope;
use App\Models\Organization;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class UserScopeUpdate
{
    public function execute(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'scope' => [new Enum(UserScope::class)],
            'organization' => 'required_if:scope,organization_wise',
            'teams' => 'required_if:scope,multiple_teams|array',
        ]);

        $scopeDetail = [];

        if ($request->scope == UserScope::ORGANIZATION_WISE->value) {
            $organization = Organization::query()
                ->with('teams')
                ->findOrFail($request->organization);

            $scopeDetail = [
                'organization' => [$organization->id],
                'teams' => $organization->teams->pluck('id')->toArray(),
            ];
        } elseif ($request->scope == UserScope::MULTIPLE_TEAMS->value) {
            $teams = Team::query()
                ->whereIn('id', $request->teams)
                ->get();

            $scopeDetail = [
                'teams' => $teams->pluck('id')->toArray(),
            ];
        }

        $user->setMeta([
            'scope' => $request->scope,
            'scope_detail' => $scopeDetail,
        ]);

        $user->save();

        $user->refresh();

        return $user;
    }
}
