<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AssignPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $acl = Arr::getVar('permission');
        $system_permissions = Arr::get($acl, 'permissions', []);

        $roles = Role::where('name', '!=', 'admin')->get();
        $permissions = Permission::all();

        $role_permission = [];
        foreach ($system_permissions as $permission_group) {
            foreach ($permission_group as $name => $assigned_roles) {
                foreach ($assigned_roles as $role) {
                    foreach ($roles->where('name', $role) as $role) {
                        $role_permission[] = [
                            'permission_id' => $permissions->firstWhere('name', $name)->id,
                            'role_id' => $role->id,
                        ];
                    }
                }
            }
        }

        \DB::table('role_has_permissions')->insert($role_permission);
    }
}
