<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;

class RoleAndPermissionExport extends Controller
{
    public function __invoke()
    {
        $roles = \DB::table('roles')
            ->where(function ($q) {
                $q->whereNull('team_id')
                    ->orWhere('team_id', auth()->user()->current_team_id);
            })
            ->where('name', '!=', 'admin')
            ->get();

        $permissions = \DB::table('permissions')
            ->get();

        $rolePermissions = \DB::table('role_has_permissions')
            ->whereIn('role_id', $roles->pluck('id')->all())
            ->get();

        $roleAndPermission = [];
        foreach ($roles as $role) {
            $roleAndPermission[$role->name] = $permissions->whereIn('id', $rolePermissions->where('role_id', $role->id)->pluck('permission_id'))->pluck('name');
        }

        $fileName = 'roles-permissions-'.date('Y-m-d').'.json';

        return response()->json($roleAndPermission)
            ->header('Content-Disposition', 'attachment; filename="'.$fileName.'"')
            ->header('Content-Type', 'application/json');
    }
}
