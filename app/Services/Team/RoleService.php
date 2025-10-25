<?php

namespace App\Services\Team;

use App\Models\Team;
use App\Models\Team\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RoleService
{
    public function create(Request $request, Team $team): Role
    {
        \DB::beginTransaction();

        $role = Role::forceCreate($this->formatParams($request, $team));

        \DB::commit();

        return $role;
    }

    private function formatParams(Request $request, Team $team, ?Role $role = null): array
    {
        $formatted = [
            'name' => Str::of($request->name)->slug('-')->value,
            'guard_name' => 'web',
            'team_id' => $team->id,
        ];

        return $formatted;
    }

    public function deletable(Team $team, Role $role): void
    {
        if ($role->team_id !== $team->id) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        if ($role->is_default) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_delete_default', ['attribute' => trans('team.config.role.role')])]);
        }
    }
}
