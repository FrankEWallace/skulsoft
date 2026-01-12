<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING ADMIN USER ===\n\n";

$user = App\Models\User::where('email', 'admin@skulsoft.com')->first();

if (!$user) {
    echo "❌ User not found! Creating new admin user...\n";
    
    $user = App\Models\User::create([
        'name' => 'System Administrator',
        'email' => 'admin@skulsoft.com',
        'username' => 'admin',
        'password' => bcrypt('admin123'),
        'status' => 'activated',
        'email_verified_at' => now(),
    ]);
    
    echo "✅ User created\n";
}

echo "Current Status:\n";
echo "  Name: {$user->name}\n";
echo "  Email: {$user->email}\n";
echo "  Status: " . ($user->status ? $user->status->value : 'NULL') . "\n";
echo "  Email Verified: " . ($user->email_verified_at ? 'Yes' : 'NO') . "\n";
echo "  Meta: " . json_encode($user->meta) . "\n";
echo "  Roles: " . $user->getRoleNames()->implode(', ') . "\n\n";

echo "=== FIXING ISSUES ===\n\n";

// Fix status (using Enum)
$user->status = App\Enums\UserStatus::ACTIVATED;
$user->email_verified_at = now();

// Get or create team
$team = App\Models\Team::first();
if (!$team) {
    $team = App\Models\Team::create(['name' => 'SkulSoft School']);
    echo "✅ Created team: {$team->name}\n";
}

// Get or create period (use correct namespace after migration)
$period = App\Domain\Academic\Models\Period::first();
if (!$period) {
    $period = App\Domain\Academic\Models\Period::create([
        'team_id' => $team->id,
        'code' => '2026-27',
        'name' => '2026-2027',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'is_default' => true,
    ]);
    echo "✅ Created period: {$period->name}\n";
}

// Assign team and period
$user->meta = [
    'current_team_id' => $team->id,
    'current_period_id' => $period->id,
];
$user->save();
echo "✅ Assigned team and period\n";

// Assign admin role with team context
$adminRole = Spatie\Permission\Models\Role::where('name', 'admin')->first();
    
if (!$adminRole) {
    echo "⚠️  Admin role not found. Please run seeders!\n";
} else {
    // Update role to have team_id if it's NULL
    if ($adminRole->team_id === null) {
        echo "⚠️  Admin role has NULL team_id. Updating to team {$team->id}...\n";
        $adminRole->team_id = $team->id;
        $adminRole->save();
        echo "✅ Updated admin role with team_id\n";
    }
    
    // Assign role with team context using setPermissionsTeamId
    app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $user->assignRole($adminRole);
    echo "✅ Assigned admin role to user\n";
}

// Clear permission cache
Artisan::call('permission:cache-reset');
echo "✅ Permission cache cleared\n";

echo "\n=== FINAL STATUS ===\n\n";
echo "Status: " . ($user->status ? $user->status->value : 'NULL') . "\n";
echo "Email Verified: " . ($user->email_verified_at ? 'Yes' : 'No') . "\n";
echo "Team: {$team->name} (ID: {$team->id})\n";
echo "Period: {$period->name} (ID: {$period->id})\n";
echo "Roles: " . $user->getRoleNames()->implode(', ') . "\n";

echo "\n✅ ALL FIXED! You can now login with:\n";
echo "   Email: admin@skulsoft.com\n";
echo "   Password: admin123\n\n";
