<?php

namespace App\Services\Team;

use App\Http\Resources\Team\RoleResource;
use App\Models\Team;
use App\Models\Team\Permission;
use App\Models\Team\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role as SpatieRole;

class PermissionService
{
    public function preRequisite(Request $request, Team $team): array
    {
        $roles = $this->getRoles($team);

        $modules = $this->getModules();

        $selectedModule = $this->getSelectedModule($request->query('module'));

        $permissions = $this->getModulePermission($selectedModule);

        $assignedPermissions = $this->generatePermissionInput($permissions, $roles);

        return compact('roles', 'modules', 'selectedModule', 'assignedPermissions');
    }

    public function roleWiseAssign(Request $request, Team $team): void
    {
        $roles = $this->getRoles($team);

        $selectedModule = $this->getSelectedModule($request->selected_module);

        $permissions = $this->getModulePermission($selectedModule);

        $this->assignPermission($request, $permissions, $roles);
    }

    public function userWiseAssign(Request $request, Team $team): void
    {
        $users = User::isNotAdmin()->whereHas('roles', function ($q) use ($team) {
            $q->where('model_has_roles.team_id', $team->id);
        })->whereIn('uuid', $request->users)->get();

        if (array_diff($request->users, $users->pluck('uuid')->all())) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        $permissions = Permission::whereIn('name', $request->permissions)->get()->pluck('name')->all();

        foreach ($users as $user) {
            if ($request->action === 'assign') {
                $user->givePermissionTo($permissions);
            } else {
                $user->revokePermissionTo($permissions);
            }
        }
    }

    private function getRoles(Team $team): AnonymousResourceCollection
    {
        $roles = Role::whereTeamId($team->id)->get();

        $spatieRoles = SpatieRole::with('permissions')->whereIn('id', $roles->pluck('id')->all())->get();

        return RoleResource::collection($spatieRoles);
    }

    private function getSelectedModule($selection = null): string
    {
        $acl = Arr::getVar('permission');
        $groupPermissions = Arr::get($acl, 'permissions');
        $moduleList = array_keys($groupPermissions);

        return in_array($selection, $moduleList) ? $selection : Arr::first($moduleList);
    }

    private function getModules(): array
    {
        $acl = Arr::getVar('permission');
        $groupPermissions = Arr::get($acl, 'permissions');

        return collect($groupPermissions)->keys()->sort()->values()->map(function ($item) {
            return ['label' => trans('module.'.$item), 'value' => $item];
        })->toArray();
    }

    private function getModulePermission($selectedModule): array
    {
        $acl = Arr::getVar('permission');
        $groupPermissions = Arr::get($acl, 'permissions');

        return array_keys(Arr::get($groupPermissions, $selectedModule, []));
    }

    private function generatePermissionInput($permissions = [], $roles = []): array
    {
        $assignedPermissions = [];
        foreach ($permissions as $permission) {
            $rows = [];
            foreach ($roles as $role) {
                $isAssigned = $role->permissions->firstWhere('name', $permission);

                $rows[] = [
                    'label' => title_case($role->name),
                    'name' => $role->name,
                    'is_assigned' => $isAssigned ? true : false,
                ];
            }

            $assignedPermissions[] = [
                'name' => $permission,
                'roles' => $rows,
            ];
        }

        return $assignedPermissions;
    }

    private function assignPermission(Request $request, $permissions = [], $roles = []): void
    {
        foreach ($roles as $role) {
            $newPermissions = [];
            foreach ($request->assigned_permissions as $assigned_permission) {
                $role_permission = Arr::first($assigned_permission['roles'], function ($item) use ($role) {
                    return $item['name'] === $role->name && $item['is_assigned'];
                });

                if ($role_permission) {
                    $newPermissions[] = Arr::get($assigned_permission, 'name');
                }
            }

            $role->revokePermissionTo($permissions);
            $role->givePermissionTo($newPermissions);
        }
    }
}
