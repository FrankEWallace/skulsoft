<?php

namespace App\Actions;

use App\Models\Team;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class CreateTeamRole
{
    public function execute(Team $team): void
    {
        $acl = Arr::getVar('permission');

        $system_roles = Arr::where(Arr::get($acl, 'roles', []), function ($item) {
            return $item != 'admin';
        });

        foreach ($system_roles as $system_role) {
            $insert_roles[] = [
                'uuid' => (string) Str::uuid(),
                'name' => $system_role,
                'guard_name' => 'web',
                'team_id' => $team->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Role::insert($insert_roles);
    }
}
