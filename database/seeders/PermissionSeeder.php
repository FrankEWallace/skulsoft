<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $acl = Arr::getVar('permission');

        $existing_permissions = Permission::get()->pluck('name')->all();
        $system_permissions = Arr::get($acl, 'permissions', []);

        $permissions = [];
        foreach ($system_permissions as $system_permission) {
            [$keys, $values] = Arr::divide($system_permission);
            $permissions = array_merge($permissions, $keys);
        }

        $remove_permissions = array_diff($existing_permissions, $permissions);

        Permission::whereIn('name', array_values($remove_permissions))->delete();

        $new_permissions = array_diff($permissions, $existing_permissions);

        $insert_permissions = [];
        foreach ($new_permissions as $permission) {
            $insert_permissions[] = [
                'uuid' => (string) Str::uuid(),
                'name' => $permission,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Permission::insert($insert_permissions);
    }
}
