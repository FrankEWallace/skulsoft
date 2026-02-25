<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates demo users for all roles for testing and demonstration purposes.
     *
     * @return void
     */
    public function run()
    {
        $team = Team::first();
        
        if (!$team) {
            $this->command->error('No team found! Please run basic seeders first.');
            return;
        }

        $defaultPassword = Hash::make('password123');
        
        // Demo users configuration
        $demoUsers = [
            [
                'name' => 'Admin User',
                'email' => 'admin@demo.com',
                'username' => 'admin',
                'role' => 'admin',
                'password' => $defaultPassword,
            ],
            [
                'name' => 'Manager Demo',
                'email' => 'manager@demo.com',
                'username' => 'manager',
                'role' => 'manager',
                'password' => $defaultPassword,
            ],
            [
                'name' => 'Principal Demo',
                'email' => 'principal@demo.com',
                'username' => 'principal',
                'role' => 'principal',
                'password' => $defaultPassword,
            ],
            [
                'name' => 'Staff Member',
                'email' => 'staff@demo.com',
                'username' => 'staff',
                'role' => 'staff',
                'password' => $defaultPassword,
            ],
            [
                'name' => 'John Accountant',
                'email' => 'accountant@demo.com',
                'username' => 'accountant',
                'role' => 'accountant',
                'password' => $defaultPassword,
            ],
            [
                'name' => 'Sarah Librarian',
                'email' => 'librarian@demo.com',
                'username' => 'librarian',
                'role' => 'librarian',
                'password' => $defaultPassword,
            ],
            [
                'name' => 'Mike Exam Coordinator',
                'email' => 'exam@demo.com',
                'username' => 'exam-coordinator',
                'role' => 'exam-incharge',
                'password' => $defaultPassword,
            ],
            [
                'name' => 'David Transport Manager',
                'email' => 'transport@demo.com',
                'username' => 'transport',
                'role' => 'transport-incharge',
                'password' => $defaultPassword,
            ],
            [
                'name' => 'Lisa Inventory Manager',
                'email' => 'inventory@demo.com',
                'username' => 'inventory',
                'role' => 'inventory-incharge',
                'password' => $defaultPassword,
            ],
            [
                'name' => 'Chef Mess Manager',
                'email' => 'mess@demo.com',
                'username' => 'mess-manager',
                'role' => 'mess-incharge',
                'password' => $defaultPassword,
            ],
            [
                'name' => 'Robert Hostel Warden',
                'email' => 'hostel@demo.com',
                'username' => 'hostel-warden',
                'role' => 'hostel-incharge',
                'password' => $defaultPassword,
            ],
            [
                'name' => 'Mary Attendance Officer',
                'email' => 'attendance@demo.com',
                'username' => 'attendance',
                'role' => 'attendance-assistant',
                'password' => $defaultPassword,
            ],
            [
                'name' => 'Emma Receptionist',
                'email' => 'reception@demo.com',
                'username' => 'receptionist',
                'role' => 'receptionist',
                'password' => $defaultPassword,
            ],
            [
                'name' => 'Tom Student',
                'email' => 'student@demo.com',
                'username' => 'student',
                'role' => 'student',
                'password' => $defaultPassword,
            ],
            [
                'name' => 'Parent Guardian',
                'email' => 'parent@demo.com',
                'username' => 'parent',
                'role' => 'guardian',
                'password' => $defaultPassword,
            ],
            [
                'name' => 'Basic User',
                'email' => 'user@demo.com',
                'username' => 'basicuser',
                'role' => 'user',
                'password' => $defaultPassword,
            ],
        ];

        $this->command->info('Creating demo users...');
        $createdCount = 0;
        $skippedCount = 0;

        foreach ($demoUsers as $userData) {
            // Check if user already exists
            $existingUser = User::where('email', $userData['email'])
                ->orWhere('username', $userData['username'])
                ->first();

            if ($existingUser) {
                $this->command->warn("User already exists: {$userData['email']}");
                $skippedCount++;
                continue;
            }

            // Get the role
            $role = Role::where('name', $userData['role'])->first();
            
            if (!$role) {
                $this->command->error("Role not found: {$userData['role']}");
                continue;
            }

            // Create user
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'username' => $userData['username'],
                'password' => $userData['password'],
                'email_verified_at' => now(),
                'status' => 'activated',
            ]);

            // Set meta for current team first
            $user->updateMeta([
                'current_team_id' => $team->id,
            ]);

            // Assign role with team_id using DB table directly
            \DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => $role->id,
                'model_type' => 'User',
                'model_id' => $user->id,
                'team_id' => $team->id,
            ]);

            $this->command->info("✓ Created: {$userData['name']} ({$userData['role']})");
            $createdCount++;
        }

        $this->command->info("Demo users creation complete!");
        $this->command->info("Created: {$createdCount} users");
        $this->command->info("Skipped: {$skippedCount} users (already exist)");
        $this->command->line('');
        $this->command->info('Default password for all demo users: password123');
        $this->command->line('');
        $this->command->table(
            ['Username', 'Email', 'Role', 'Password'],
            collect($demoUsers)->map(fn($u) => [
                $u['username'],
                $u['email'],
                $u['role'],
                'password123'
            ])->toArray()
        );
    }
}
