<?php

namespace Mint\Service\Actions;

use Closure;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class SeedRoleAndPermission
{
    public function handle($params, Closure $next)
    {
        Role::create([
            'name' => 'admin',
            'uuid' => (string) Str::uuid(),
            'team_id' => null
        ]);

        \Artisan::call('db:seed', ['--class' => 'RoleSeeder', '--force' => true]);
        \Artisan::call('db:seed', ['--class' => 'PermissionSeeder', '--force' => true]);
        \Artisan::call('db:seed', ['--class' => 'AssignPermissionSeeder', '--force' => true]);

        return $next($params);
    }
}
