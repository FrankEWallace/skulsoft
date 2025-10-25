<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class QuickFixSeeder extends Seeder
{
    /**
     * Run the database seeds to fix login issues
     */
    public function run()
    {
        $this->command->info('🔧 Quick Fix Seeder - Fixing critical login issues...');

        $team_id = 1;
        $period_id = 1;

        // 1. Fix admin role - ensure it has team_id
        $this->command->info('Fixing admin role...');
        DB::table('roles')->where('id', 1)->update(['team_id' => $team_id]);

        // 2. Fix admin user meta
        $this->command->info('Fixing admin user metadata...');
        $user = DB::table('users')->where('id', 1)->first();
        if ($user) {
            $meta = json_decode($user->meta ?? '{}', true);
            $meta['current_team_id'] = $team_id;
            $meta['current_period_id'] = $period_id;
            $meta['is_default'] = true;
            
            DB::table('users')->where('id', 1)->update([
                'meta' => json_encode($meta),
                'password' => Hash::make('admin123'), // Reset to known password
                'status' => 'activated',
                'updated_at' => now(),
            ]);
            
            $this->command->info('✓ Admin user fixed - Password reset to: admin123');
        }

        // 3. Ensure model_has_roles has team_id
        $this->command->info('Fixing role assignments...');
        DB::table('model_has_roles')
            ->where('model_id', 1)
            ->where('role_id', 1)
            ->update(['team_id' => $team_id]);

        // 4. Ensure period exists and is marked as default
        $this->command->info('Checking academic period...');
        $period = DB::table('periods')->where('id', $period_id)->first();
        if ($period) {
            DB::table('periods')->where('id', $period_id)->update(['is_default' => 1]);
            $this->command->info('✓ Period marked as default');
        }

        // 5. Verify the fix
        $this->command->info(PHP_EOL . '📊 Verification:');
        $user = DB::table('users')->where('id', 1)->first();
        $meta = json_decode($user->meta, true);
        
        $this->command->info('  User ID: ' . $user->id);
        $this->command->info('  Name: ' . $user->name);
        $this->command->info('  Email: ' . $user->email);
        $this->command->info('  Status: ' . $user->status);
        $this->command->info('  Current Team ID: ' . ($meta['current_team_id'] ?? 'NOT SET'));
        $this->command->info('  Current Period ID: ' . ($meta['current_period_id'] ?? 'NOT SET'));
        
        $roleAssignment = DB::table('model_has_roles')
            ->where('model_id', 1)
            ->where('role_id', 1)
            ->first();
        $this->command->info('  Role Team ID: ' . ($roleAssignment->team_id ?? 'NOT SET'));

        $this->command->info(PHP_EOL . '✅ Quick fix completed!');
        $this->command->info('📝 Login credentials:');
        $this->command->info('   Email: admin@example.com');
        $this->command->info('   Password: admin123');
    }
}
