<?php

namespace App\Services\Team;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RoleAndPermissionImportService
{
    public function import(Request $request)
    {
        if (! $request->file('file')) {
            throw ValidationException::withMessages(['message' => trans('validation.required', ['attribute' => trans('general.file')])]);
        }

        $extension = $request->file('file')->getClientOriginalExtension();

        if (! in_array($extension, ['json'])) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        try {
            $data = \File::json($request->file('file'));
        } catch (\Exception $e) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_format')]);
        }

        // sample data
        // {"user":["login:action","chat:access","todo:manage"]}

        $importedRoles = array_keys($data);

        $roles = \DB::table('roles')
            ->where(function ($q) {
                $q->whereNull('team_id')
                    ->orWhere('team_id', auth()->user()->current_team_id);
            })
            ->where('name', '!=', 'admin')
            ->get();

        $permissions = \DB::table('permissions')
            ->get();

        $rows = [];
        foreach ($importedRoles as $importedRole) {
            $role = $roles->firstWhere('name', $importedRole);

            if (! $role) {
                $roleId = \DB::table('roles')
                    ->insertGetId([
                        'uuid' => (string) Str::uuid(),
                        'team_id' => auth()->user()->current_team_id,
                        'name' => $importedRole,
                        'guard_name' => 'web',
                    ]);
            } else {
                $roleId = $role->id;
            }

            \DB::table('role_has_permissions')
                ->where('role_id', $roleId)
                ->delete();

            $importedPermissions = Arr::get($data, $importedRole, []);

            foreach ($importedPermissions as $importedPermission) {
                $permission = $permissions->firstWhere('name', $importedPermission);

                if (! $permission) {
                    continue;
                }

                $rows[] = [
                    'role_id' => $roleId,
                    'permission_id' => $permission->id,
                ];
            }
        }

        if (count($rows) > 0) {
            \DB::table('role_has_permissions')->insert($rows);
        }
    }
}
