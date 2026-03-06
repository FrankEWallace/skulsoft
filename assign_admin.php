<?php

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Models\User;

$email = 'admin@mewogstars.sc.tz';
$teamId = 1;

$user = User::where('email', $email)->first();
if (!$user) { die("User not found!\n"); }

$adminRole = Role::where('name', 'admin')->first();
if (!$adminRole) { die("Admin role not found!\n"); }

// Ensure role is assigned with correct model_type (full namespace)
$exists = DB::table('model_has_roles')
    ->where('role_id', $adminRole->id)
    ->where('model_id', $user->id)
    ->where('model_type', 'App\\Models\\User')
    ->exists();

if (!$exists) {
    DB::table('model_has_roles')->insert([
        'role_id'    => $adminRole->id,
        'model_type' => 'App\\Models\\User',
        'model_id'   => $user->id,
        'team_id'    => $teamId,
    ]);
    echo "Role assigned to: " . $user->email . "\n";
} else {
    echo "Role already assigned to: " . $user->email . "\n";
}

// Set current_team_id and is_default in user meta
$meta = $user->meta ?? [];
if (!is_array($meta)) { $meta = json_decode($meta, true) ?? []; }
$meta['current_team_id'] = $teamId;
$meta['is_default'] = true;  // Bypass login:action permission check for admin
DB::table('users')->where('id', $user->id)->update(['meta' => json_encode($meta)]);
echo "Set current_team_id=$teamId and is_default=true in user meta.\n";

// Also fix getAllowedTeamIds bug: model_type must be full class name, not 'User'
// Correct any bad entries
$fixed = DB::table('model_has_roles')
    ->where('model_id', $user->id)
    ->where('model_type', 'User')
    ->update(['model_type' => 'App\\Models\\User']);
if ($fixed) echo "Fixed $fixed model_has_roles rows with short model_type.\n";

// Clear permission cache
app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
echo "Permission cache cleared.\n";

echo "User ID: " . $user->id . "\n";
echo "Role ID: " . $adminRole->id . "\n";
echo "Done! Try logging in with: $email\n";
