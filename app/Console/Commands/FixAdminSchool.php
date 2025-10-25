<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixAdminSchool extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:fix-school';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix admin users school/team assignment';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Checking for existing schools/teams...');

        // Get all teams
        $teams = Team::all();

        if ($teams->isEmpty()) {
            $this->warn('No schools found. Creating default school...');
            
            // Create a default team/school
            $team = Team::create([
                'name' => 'Default School',
                'alias' => 'default-school',
                'meta' => [],
            ]);
            
            $this->info("Created school: {$team->name} (ID: {$team->id})");
        } else {
            $team = $teams->first();
            $this->info("Found existing school: {$team->name} (ID: {$team->id})");
        }

        // Find admin user(s)
        $this->info('Looking for admin users...');

        $adminUsers = User::whereHas('roles', function($query) {
            $query->where('name', 'admin');
        })->get();

        if ($adminUsers->isEmpty()) {
            $this->error('No admin users found!');
            $this->error('Please create an admin user first.');
            return 1;
        }

        $bar = $this->output->createProgressBar($adminUsers->count());
        $bar->start();

        foreach ($adminUsers as $user) {
            $this->newLine();
            $this->info("Processing user: {$user->name} ({$user->email})");
            
            // Check if user has team assignment
            $hasTeam = DB::table('model_has_roles')
                ->where('model_id', $user->id)
                ->where('model_type', 'User')
                ->where('team_id', $team->id)
                ->exists();
            
            if (!$hasTeam) {
                $this->comment('  → User is not assigned to team. Updating role assignment...');
                
                // Update all user's roles to include team_id
                DB::table('model_has_roles')
                    ->where('model_id', $user->id)
                    ->where('model_type', 'User')
                    ->update(['team_id' => $team->id]);
                
                $this->info('  ✓ Updated role assignments');
            } else {
                $this->comment('  → User already assigned to team');
            }
            
            // Set current_team_id in user meta
            $currentTeamId = $user->getMeta('current_team_id');
            
            if (!$currentTeamId || $currentTeamId != $team->id) {
                $this->comment("  → Setting current_team_id to {$team->id}");
                $user->updateMeta(['current_team_id' => $team->id]);
                $this->info('  ✓ Updated user meta');
            } else {
                $this->comment('  → Current team already set correctly');
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('✅ Done! Admin users should now be able to login.');
        $this->info('Please try logging in again.');

        return 0;
    }
}
