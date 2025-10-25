<?php

namespace App\Services;

use App\Models\Academic\Period;
use App\Models\Team;
use App\Models\Team\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TeamActionService
{
    public function select(Request $request, Team $team): void
    {
        $user = \Auth::user();

        if ($user->current_team_id == $team->id) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        if (! in_array($team->id, config('config.teams', []))) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        $newSelectionHistory = $user->updateSelectionHistory([
            'team_id' => $user->current_team_id,
            'period_id' => $user->current_period_id,
        ]);

        $selectionHistory = collect($user->meta['selection_history'] ?? []);
        $existingTeamSelectionHistory = $selectionHistory->where('team_id', $team->id)->first();

        if ($existingTeamSelectionHistory && Arr::get($existingTeamSelectionHistory, 'period_id')) {
            $preference = $user->preference;
            $preference['academic']['period_id'] = Arr::get($existingTeamSelectionHistory, 'period_id');
            $user->preference = $preference;
        }

        $meta = $user->meta;
        $meta['current_team_id'] = $team->id;
        $meta['selection_history'] = $newSelectionHistory;
        $user->meta = $meta;
        $user->save();

        session()->put(['team_id' => $team->id]);
    }

    public function getPeriods(Request $request, Team $team): Collection
    {
        $periods = Period::query()
            ->where('team_id', $team->id)
            ->get();

        return $periods;
    }

    public function storeConfig(Request $request, Team $team): void
    {
        $team->setConfig([
            'name' => $request->name,
            'title1' => $request->title1,
            'title2' => $request->title2,
            'title3' => $request->title3,
            'address_line1' => $request->address_line1,
            'address_line2' => $request->address_line2,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country,
            'zipcode' => $request->zipcode,
            'phone' => $request->phone,
            'email' => $request->email,
            'website' => $request->website,
            'fax' => $request->fax,
            'incharge1' => [
                'title' => $request->input('incharge1.title'),
                'name' => $request->input('incharge1.name'),
                'email' => $request->input('incharge1.email'),
                'contact_number' => $request->input('incharge1.contact_number'),
            ],
            'incharge2' => [
                'title' => $request->input('incharge2.title'),
                'name' => $request->input('incharge2.name'),
                'email' => $request->input('incharge2.email'),
                'contact_number' => $request->input('incharge2.contact_number'),
            ],
            'incharge3' => [
                'title' => $request->input('incharge3.title'),
                'name' => $request->input('incharge3.name'),
                'email' => $request->input('incharge3.email'),
                'contact_number' => $request->input('incharge3.contact_number'),
            ],
            'incharge4' => [
                'title' => $request->input('incharge4.title'),
                'name' => $request->input('incharge4.name'),
                'email' => $request->input('incharge4.email'),
                'contact_number' => $request->input('incharge4.contact_number'),
            ],
            'incharge5' => [
                'title' => $request->input('incharge5.title'),
                'name' => $request->input('incharge5.name'),
                'email' => $request->input('incharge5.email'),
                'contact_number' => $request->input('incharge5.contact_number'),
            ],
        ], true);
    }

    public function sync(Request $request, Team $team): void
    {
        $existingTeam = Team::query()
            ->whereUuid($request->team)
            ->getOrFail(trans('team.team'));

        if ($existingTeam->id == $team->id) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        $roles = Role::query()
            ->where('team_id', $existingTeam->id)
            ->get();

        $syncedRoles = [];
        foreach ($roles as $existingRole) {
            $teamRole = Role::firstOrCreate([
                'team_id' => $team->id,
                'name' => $existingRole->name,
                'guard_name' => 'web',
            ]);
            $syncedRoles[$existingRole->id] = $teamRole->id;
        }

        if ($request->boolean('permission')) {
            $teamRoleIds = Role::where('team_id', $team->id)->pluck('id');
            \DB::table('role_has_permissions')->whereIn('role_id', $teamRoleIds)->delete();

            $existingPermissions = \DB::table('role_has_permissions')
                ->whereIn('role_id', array_keys($syncedRoles))
                ->get();

            $newPermissions = $existingPermissions->map(function ($permission) use ($syncedRoles) {
                return [
                    'role_id' => $syncedRoles[$permission->role_id],
                    'permission_id' => $permission->permission_id,
                ];
            });

            \DB::table('role_has_permissions')->insert($newPermissions->toArray());
        }

        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
