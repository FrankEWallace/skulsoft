<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $acl = Arr::getVar('permission');

        foreach (Team::get() as $team) {
            $existing_roles = Role::query()
                ->where(function ($q) use ($team) {
                    $q->whereNull('team_id')
                        ->orWhere('team_id', $team->id);
                })
                ->get()
                ->pluck('name')
                ->all();
            $system_roles = Arr::get($acl, 'roles', []);

            // Remove roles causing deletion of custom roles so we'll comment it
            // $remove_roles = array_diff($existing_roles, $system_roles);
            // Role::query()
            //     ->where(function ($q) use ($team) {
            //         $q->whereNull('team_id')
            //             ->orWhere('team_id', $team->id);
            //     })
            //     ->whereIn('name', array_values($remove_roles))->delete();

            $new_roles = array_diff($system_roles, $existing_roles);

            $insert_roles = [];
            foreach ($new_roles as $role) {
                $insert_roles[] = [
                    'uuid' => (string) Str::uuid(),
                    'name' => $role,
                    'guard_name' => 'web',
                    'team_id' => $role == 'admin' ? null : $team->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Role::insert($insert_roles);
        }
    }
}
