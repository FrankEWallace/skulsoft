<?php
/**
 * Fix admin role assignment - DELETE after use!
 * Visit: https://sims.mewogstars.sc.tz/fix_admin_role.php
 */

define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain');

use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

$log = [];

$teamId = 1;
$userId = 1;
$roleName = 'admin';

// Step 1: Check current model_has_roles entries for this user
$existing = DB::table('model_has_roles')
    ->where('model_id', $userId)
    ->where('model_type', 'App\\Models\\User')
    ->get();

$log[] = "Current model_has_roles entries for user_id={$userId}:";
if ($existing->isEmpty()) {
    $log[] = "  NONE FOUND";
} else {
    foreach ($existing as $row) {
        $log[] = "  role_id={$row->role_id}, team_id=" . ($row->team_id ?? 'NULL') . ", model_type={$row->model_type}";
    }
}

// Step 2: Find the admin role
$adminRole = DB::table('roles')->where('name', $roleName)->first();
if (!$adminRole) {
    $log[] = "ERROR: 'admin' role not found in roles table!";
    // List available roles
    $roles = DB::table('roles')->get(['id', 'name', 'team_id']);
    $log[] = "Available roles:";
    foreach ($roles as $r) {
        $log[] = "  id={$r->id}, name={$r->name}, team_id=" . ($r->team_id ?? 'NULL');
    }
} else {
    $log[] = "Found 'admin' role: id={$adminRole->id}, team_id=" . ($adminRole->team_id ?? 'NULL');
}

// Step 3: Clean up any existing entries for this user+role and re-insert with correct team_id
if ($adminRole) {
    // Delete any existing (wrong or right) entries for this user+role
    $deleted = DB::table('model_has_roles')
        ->where('model_id', $userId)
        ->where('model_type', 'App\\Models\\User')
        ->where('role_id', $adminRole->id)
        ->delete();
    $log[] = "Deleted {$deleted} existing role assignment(s) for user_id={$userId} role_id={$adminRole->id}";

    // Insert fresh with team_id=1
    DB::table('model_has_roles')->insert([
        'role_id'    => $adminRole->id,
        'model_type' => 'App\\Models\\User',
        'model_id'   => $userId,
        'team_id'    => $teamId,
    ]);
    $log[] = "Inserted: role_id={$adminRole->id}, model_id={$userId}, model_type=App\\Models\\User, team_id={$teamId}";
}

// Step 4: Clear permission cache
app(PermissionRegistrar::class)->forgetCachedPermissions();
$log[] = "Permission cache cleared";

// Step 5: Raw DB check - what's actually in model_has_roles now
$fresh = DB::table('model_has_roles')
    ->where('model_id', $userId)
    ->where('model_type', 'App\\Models\\User')
    ->get();
$log[] = "Raw model_has_roles after fix:";
foreach ($fresh as $row) {
    $log[] = "  role_id={$row->role_id}, team_id=" . ($row->team_id ?? 'NULL') . ", model_type={$row->model_type}";
}

// Step 5b: Check permission config team setting
$log[] = "permission.teams config: " . (config('permission.teams') ? 'true' : 'false');
$log[] = "PermissionRegistrar teamId before set: " . (app(PermissionRegistrar::class)->getPermissionsTeamId() ?? 'NULL');

// Step 5c: Verify using Spatie's exact query path
app(PermissionRegistrar::class)->forgetCachedPermissions();
app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);
$log[] = "PermissionRegistrar teamId after set: " . (app(PermissionRegistrar::class)->getPermissionsTeamId() ?? 'NULL');

// Must reload user AFTER setting team id
$user = \App\Models\User::find($userId);
$roles = $user->getRoleNames();
$log[] = "getRoleNames() with teamId={$teamId}: " . ($roles->isEmpty() ? 'STILL NONE ✗' : $roles->implode(', ') . ' ✓');

// Step 5d: Try with setPermissionsTeamId via config
$log[] = "Trying via SysHelper::setTeam()...";
\App\Helpers\SysHelper::setTeam($teamId);
app(PermissionRegistrar::class)->forgetCachedPermissions();
$user2 = \App\Models\User::find($userId);
$roles2 = $user2->getRoleNames();
$log[] = "getRoleNames() after SysHelper::setTeam({$teamId}): " . ($roles2->isEmpty() ? 'STILL NONE ✗' : $roles2->implode(', ') . ' ✓');

// Step 5e: Direct role check bypassing team
$allRoles = DB::table('model_has_roles')
    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
    ->where('model_has_roles.model_id', $userId)
    ->select('roles.name', 'model_has_roles.team_id')
    ->get();
$log[] = "Direct DB role names: " . ($allRoles->isEmpty() ? 'NONE' : $allRoles->map(fn($r) => $r->name . '(team_id=' . ($r->team_id ?? 'NULL') . ')')->implode(', '));

// Step 5f: Check what SysHelper::setTeam actually does to the PermissionRegistrar
$log[] = "After SysHelper::setTeam, PermissionRegistrar teamId: " . (app(PermissionRegistrar::class)->getPermissionsTeamId() ?? 'NULL');

// Step 6: Test ValidateRole using same path as Login action
try {
    // Replicate exactly what Login::getUser() does
    \App\Helpers\SysHelper::setTeam($teamId);
    app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user3 = \App\Models\User::find($userId);
    $validateRole = new \App\Actions\Auth\ValidateRole();
    $validateRole->execute($user3);
    $log[] = "ValidateRole::execute(): PASSED ✓";
} catch (\Exception $e) {
    $log[] = "ValidateRole::execute() FAILED: " . $e->getMessage();
}

// Step 7: Also ensure user meta is correct
$metaTeam = $user->getMeta('current_team_id');
$metaDefault = $user->getMeta('is_default');
$log[] = "user meta current_team_id: " . ($metaTeam ?? 'NULL');
$log[] = "user meta is_default: " . ($metaDefault ? 'true' : 'false/null');

if (!$metaTeam || $metaTeam != $teamId) {
    $user->setMeta('current_team_id', $teamId);
    $user->save();
    $log[] = "Fixed: set current_team_id={$teamId} in user meta";
}
if (!$metaDefault) {
    $user->setMeta('is_default', true);
    $user->save();
    $log[] = "Fixed: set is_default=true in user meta";
}

echo "=== ADMIN ROLE FIX ===\n\n";
foreach ($log as $line) {
    echo $line . "\n";
}
echo "\n=== DONE ===\n";
echo "\n⚠️  DELETE THIS FILE AFTER USE!\n";
