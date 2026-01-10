#!/usr/bin/env php
<?php

/**
 * SkulSoft Complete Setup Script
 * This ensures all permissions and settings are properly configured
 */

define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🚀 Starting SkulSoft Complete Setup...\n\n";

try {
    // Step 1: Create/Update User
    echo "👤 Step 1: Setting up admin user...\n";
    $user = App\Models\User::updateOrCreate(
        ['email' => 'admin@skulsoft.com'],
        [
            'name' => 'System Administrator',
            'username' => 'admin',
            'password' => bcrypt('admin123'),
            'status' => 'activated',
            'email_verified_at' => now(),
        ]
    );
    echo "✅ Admin user created/updated (ID: {$user->id})\n\n";

    // Step 2: Create Team
    echo "🏫 Step 2: Setting up school/team...\n";
    $team = App\Models\Team::firstOrCreate(
        ['name' => 'SkulSoft School']
    );
    echo "✅ Team created (ID: {$team->id})\n\n";

    // Step 3: Create Period
    echo "📅 Step 3: Setting up academic period...\n";
    $period = App\Models\Academic\Period::firstOrCreate(
        [
            'team_id' => $team->id,
            'code' => '2026-27'
        ],
        [
            'name' => '2026-2027',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_default' => true,
        ]
    );
    echo "✅ Academic period created (ID: {$period->id})\n\n";

    // Step 4: Assign Team and Period to User
    echo "🔗 Step 4: Linking user to team and period...\n";
    $user->meta = [
        'current_team_id' => $team->id,
        'current_period_id' => $period->id,
    ];
    $user->save();
    echo "✅ User linked to team and period\n\n";

    // Step 5: Create Admin Role
    echo "👑 Step 5: Setting up admin role...\n";
    $adminRole = Spatie\Permission\Models\Role::firstOrCreate([
        'name' => 'admin',
        'guard_name' => 'web',
    ]);
    echo "✅ Admin role created (ID: {$adminRole->id})\n\n";

    // Step 6: Assign Role to User with Team
    echo "🎭 Step 6: Assigning role to user...\n";
    DB::table('model_has_roles')->updateOrInsert(
        [
            'role_id' => $adminRole->id,
            'model_type' => 'App\\Models\\User',
            'model_id' => $user->id,
        ],
        ['team_id' => $team->id]
    );
    echo "✅ Role assigned with team_id\n\n";

    // Step 7: Give all permissions to admin role (if permissions exist)
    echo "🔐 Step 7: Assigning permissions...\n";
    $permissions = Spatie\Permission\Models\Permission::all();
    if ($permissions->count() > 0) {
        $adminRole->syncPermissions($permissions);
        echo "✅ {$permissions->count()} permissions assigned to admin role\n\n";
    } else {
        echo "⚠️  No permissions found in database\n";
        echo "   Creating basic admin permission...\n";
        $adminPermission = Spatie\Permission\Models\Permission::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);
        $adminRole->givePermissionTo($adminPermission);
        echo "✅ Basic admin permission created and assigned\n\n";
    }

    // Step 8: Clear caches
    echo "🧹 Step 8: Clearing caches...\n";
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    echo "✅ Caches cleared\n\n";

    // Final Summary
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "🎉 SETUP COMPLETE!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "📊 Summary:\n";
    echo "  • User ID: {$user->id}\n";
    echo "  • Email: {$user->email}\n";
    echo "  • Password: admin123\n";
    echo "  • Status: {$user->status}\n";
    echo "  • Team: {$team->name} (ID: {$team->id})\n";
    echo "  • Period: {$period->name} (ID: {$period->id})\n";
    echo "  • Role: admin\n";
    echo "  • Permissions: " . ($permissions->count() > 0 ? $permissions->count() : '1 (basic)') . "\n\n";
    
    echo "🔐 LOGIN CREDENTIALS:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "  URL:      http://127.0.0.1:8002\n";
    echo "  Email:    admin@skulsoft.com\n";
    echo "  Password: admin123\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "✅ You can now login to SkulSoft!\n\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\n📋 Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
