<?php

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Models\User;

setPermissionsTeamId(1);

$user = User::where('email', 'admin@mewogstars.sc.tz')->first();
$adminRole = Role::where('name', 'admin')->whereNull('team_id')->first();

if (!$user) { die("User not found!\n"); }
if (!$adminRole) { die("Admin role not found!\n"); }

// Check if already assigned
$exists = DB::table('model_has_roles')
    ->where('role_id', $adminRole->id)
    ->where('model_id', $user->id)
    ->where('model_type', 'App\Models\User')
    ->exists();

if ($exists) {
    echo "Role already assigned to: " . $user->email . "\n";
} else {
    DB::table('model_has_roles')->insert([
        'role_id'    => $adminRole->id,
        'model_type' => 'App\Models\User',
        'model_id'   => $user->id,
        'team_id'    => 1,
    ]);
    echo "Role assigned successfully to: " . $user->email . "\n";
}

echo "User ID: " . $user->id . "\n";
echo "Role ID: " . $adminRole->id . "\n";
