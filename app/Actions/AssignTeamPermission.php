<?php

namespace App\Actions;

use App\Models\Team;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AssignTeamPermission
{
    public function execute(Team $team): void
    {
        $acl = Arr::getVar('permission');
        $system_permissions = Arr::get($acl, 'permissions', []);

        $roles = Role::where('name', '!=', 'admin')->whereTeamId($team->id)->get();
        $permissions = Permission::all();

        $role_permission = [];
        foreach ($system_permissions as $permission_group) {
            foreach ($permission_group as $name => $assigned_roles) {
                foreach ($assigned_roles as $role) {
                    $role_permission[] = [
                        'permission_id' => $permissions->firstWhere('name', $name)->id,
                        'role_id' => $roles->firstWhere('name', $role)->id,
                    ];
                }
            }
        }

        \DB::table('role_has_permissions')->insert($role_permission);
    }
}
