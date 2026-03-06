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

// The app defines a morphMap in ModelRelation.php: 'User' => 'App\Models\User'
// So model_has_roles.model_type must be 'User', NOT 'App\Models\User'
$correctModelType = 'User';

// Step 1: Show all existing entries (any model_type)
$all = DB::table('model_has_roles')->where('model_id', $userId)->get();
$log[] = "All model_has_roles for user_id={$userId}:";
if ($all->isEmpty()) {
    $log[] = "  NONE";
} else {
    foreach ($all as $row) {
        $log[] = "  role_id={$row->role_id}, team_id=" . ($row->team_id ?? 'NULL') . ", model_type={$row->model_type}";
    }
}

// Step 2: Find the admin role
$adminRole = DB::table('roles')->where('name', $roleName)->first();
$log[] = $adminRole
    ? "Found '{$roleName}' role: id={$adminRole->id}, team_id=" . ($adminRole->team_id ?? 'NULL')
    : "ERROR: '{$roleName}' role not found!";

if ($adminRole) {
    // Step 3: Delete ALL existing entries for this user regardless of model_type
    $deleted = DB::table('model_has_roles')->where('model_id', $userId)->delete();
    $log[] = "Deleted all {$deleted} role assignment(s) for user_id={$userId}";

    // Step 4: Insert with CORRECT morph alias 'User'
    DB::table('model_has_roles')->insert([
        'role_id'    => $adminRole->id,
        'model_type' => $correctModelType,
        'model_id'   => $userId,
        'team_id'    => $teamId,
    ]);
    $log[] = "Inserted: role_id={$adminRole->id}, model_type={$correctModelType}, model_id={$userId}, team_id={$teamId}";
}

// Step 5: Clear all caches
\Illuminate\Support\Facades\Cache::flush();
app(PermissionRegistrar::class)->forgetCachedPermissions();
$log[] = "All caches cleared";

// Step 6: Verify raw DB
$fresh = DB::table('model_has_roles')->where('model_id', $userId)->get();
$log[] = "Raw model_has_roles after fix:";
foreach ($fresh as $row) {
    $log[] = "  role_id={$row->role_id}, team_id=" . ($row->team_id ?? 'NULL') . ", model_type={$row->model_type}";
}

// Step 7: Test getRoleNames() with team context
app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);
$log[] = "PermissionRegistrar teamId set to: " . app(PermissionRegistrar::class)->getPermissionsTeamId();

$user = \App\Models\User::find($userId);
$rolesQuery = $user->roles()->toBase();
$log[] = "roles() SQL: " . $rolesQuery->toSql();
$log[] = "roles() bindings: " . json_encode($rolesQuery->getBindings());
$log[] = "roles() result count: " . $rolesQuery->get()->count();

$roles = $user->getRoleNames();
$log[] = "getRoleNames(): " . ($roles->isEmpty() ? 'STILL NONE ✗' : $roles->implode(', ') . ' ✓');

// Step 8: Test ValidateRole
try {
    \App\Helpers\SysHelper::setTeam($teamId);
    app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user2 = \App\Models\User::find($userId);
    $validateRole = new \App\Actions\Auth\ValidateRole();
    $validateRole->execute($user2);
    $log[] = "ValidateRole::execute(): PASSED ✓ LOGIN SHOULD NOW WORK!";
} catch (\Exception $e) {
    $log[] = "ValidateRole::execute() FAILED: " . $e->getMessage();
}

echo "=== ADMIN ROLE FIX ===\n\n";
foreach ($log as $line) {
    echo $line . "\n";
}
echo "\n=== DONE ===\n";
echo "\n⚠️  DELETE THIS FILE AFTER USE!\n";
