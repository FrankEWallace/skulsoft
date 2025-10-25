<?php
/**
 * Fix Admin School Assignment
 * 
 * Run this script from command line:
 * php fix_admin_school.php
 * 
 * This script will:
 * 1. Check if there are any teams/schools in the database
 * 2. Create a default school if none exists
 * 3. Assign the admin user to that school
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "Checking for existing schools/teams...\n";

// Get all teams
$teams = Team::all();

if ($teams->isEmpty()) {
    echo "No schools found. Creating default school...\n";
    
    // Create a default team/school
    $team = Team::create([
        'name' => 'Default School',
        'alias' => 'default-school',
        'meta' => [],
    ]);
    
    echo "Created school: {$team->name} (ID: {$team->id})\n";
} else {
    $team = $teams->first();
    echo "Found existing school: {$team->name} (ID: {$team->id})\n";
}

// Find admin user(s)
echo "\nLooking for admin users...\n";

$adminUsers = User::whereHas('roles', function($query) {
    $query->where('name', 'admin');
})->get();

if ($adminUsers->isEmpty()) {
    echo "ERROR: No admin users found!\n";
    echo "Please create an admin user first.\n";
    exit(1);
}

foreach ($adminUsers as $user) {
    echo "\nProcessing user: {$user->name} ({$user->email})\n";
    
    // Check if user has team assignment
    $hasTeam = DB::table('model_has_roles')
        ->where('model_id', $user->id)
        ->where('model_type', 'User')
        ->where('team_id', $team->id)
        ->exists();
    
    if (!$hasTeam) {
        echo "  - User is not assigned to team. Updating role assignment...\n";
        
        // Update all user's roles to include team_id
        DB::table('model_has_roles')
            ->where('model_id', $user->id)
            ->where('model_type', 'User')
            ->update(['team_id' => $team->id]);
        
        echo "  - Updated role assignments\n";
    } else {
        echo "  - User already assigned to team\n";
    }
    
    // Set current_team_id in user meta
    $currentTeamId = $user->getMeta('current_team_id');
    
    if (!$currentTeamId || $currentTeamId != $team->id) {
        echo "  - Setting current_team_id to {$team->id}\n";
        $user->updateMeta(['current_team_id' => $team->id]);
        echo "  - Updated user meta\n";
    } else {
        echo "  - Current team already set correctly\n";
    }
}

echo "\n✅ Done! Admin users should now be able to login.\n";
echo "Please try logging in again.\n";
