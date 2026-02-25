<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    private $teamId = 1;
    private $periodId;
    private $batchIds = [];
    private $courseIds = [];
    private $departmentIds = [];
    private $designationIds = [];
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "\n🚀 Starting Demo Data Seeding...\n\n";
        
        // Get or create period
        $this->periodId = $this->getOrCreatePeriod();
        
        // Create organizational structure
        $this->createDepartments();
        $this->createDesignations();
        $this->createCourses();
        $this->createBatches();
        
        // Create demo employee contacts and records
        $this->createEmployeeData();
        
        // Create demo student contacts and records
        $this->createStudentData();
        
        // Create demo guardian data
        $this->createGuardianData();
        
        echo "\n✅ Demo Data Seeding Complete!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📊 Summary:\n";
        echo "   • Period: " . DB::table('periods')->find($this->periodId)->name . "\n";
        echo "   • Departments: " . count($this->departmentIds) . "\n";
        echo "   • Designations: " . count($this->designationIds) . "\n";
        echo "   • Courses: " . count($this->courseIds) . "\n";
        echo "   • Batches: " . count($this->batchIds) . "\n";
        echo "   • Employee Contacts: " . DB::table('contacts')->where('team_id', $this->teamId)->whereIn('meta->source', ['employee'])->count() . "\n";
        echo "   • Student Contacts: " . DB::table('contacts')->where('team_id', $this->teamId)->whereIn('meta->source', ['student'])->count() . "\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    }
    
    private function getOrCreatePeriod()
    {
        $period = DB::table('periods')->where('team_id', $this->teamId)->first();
        
        if (!$period) {
            echo "📅 Creating Academic Period...\n";
            $periodId = DB::table('periods')->insertGetId([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'team_id' => $this->teamId,
                'name' => '2025-2026',
                'code' => '2025-26',
                'start_date' => '2025-09-01',
                'end_date' => '2026-06-30',
                'description' => 'Demo Academic Year',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "   ✓ Created period: 2025-2026\n";
            return $periodId;
        }
        
        echo "📅 Using existing period: {$period->name}\n";
        return $period->id;
    }
    
    private function createDepartments()
    {
        echo "\n🏢 Creating Departments...\n";
        
        $departments = [
            ['name' => 'Administration', 'description' => 'Administrative Department'],
            ['name' => 'Academics', 'description' => 'Academic Department'],
            ['name' => 'Accounts', 'description' => 'Accounts & Finance Department'],
            ['name' => 'Library', 'description' => 'Library Department'],
            ['name' => 'Transport', 'description' => 'Transport Department'],
            ['name' => 'Examination', 'description' => 'Examination Department'],
        ];
        
        foreach ($departments as $dept) {
            $existing = DB::table('departments')
                ->where('team_id', $this->teamId)
                ->where('name', $dept['name'])
                ->first();
                
            if (!$existing) {
                $id = DB::table('departments')->insertGetId([
                    'uuid' => \Illuminate\Support\Str::uuid(),
                    'team_id' => $this->teamId,
                    'name' => $dept['name'],
                    'description' => $dept['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->departmentIds[$dept['name']] = $id;
                echo "   ✓ Created: {$dept['name']}\n";
            } else {
                $this->departmentIds[$dept['name']] = $existing->id;
                echo "   • Using existing: {$dept['name']}\n";
            }
        }
    }
    
    private function createDesignations()
    {
        echo "\n👔 Creating Designations...\n";
        
        $designations = [
            'Principal',
            'Vice Principal',
            'Senior Teacher',
            'Teacher',
            'Accountant',
            'Librarian',
            'Transport Manager',
            'Exam Coordinator',
        ];
        
        foreach ($designations as $name) {
            $existing = DB::table('designations')
                ->where('team_id', $this->teamId)
                ->where('name', $name)
                ->first();
                
            if (!$existing) {
                $id = DB::table('designations')->insertGetId([
                    'uuid' => \Illuminate\Support\Str::uuid(),
                    'team_id' => $this->teamId,
                    'name' => $name,
                    'position' => count($this->designationIds) + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->designationIds[$name] = $id;
                echo "   ✓ Created: {$name}\n";
            } else {
                $this->designationIds[$name] = $existing->id;
                echo "   • Using existing: {$name}\n";
            }
        }
    }
    
    private function createCourses()
    {
        echo "\n📚 Creating/Checking Courses...\n";
        
        $coursesData = [
            ['name' => 'Grade 1', 'code' => 'GR1', 'position' => 1],
            ['name' => 'Grade 2', 'code' => 'GR2', 'position' => 2],
            ['name' => 'Grade 3', 'code' => 'GR3', 'position' => 3],
            ['name' => 'Grade 4', 'code' => 'GR4', 'position' => 4],
            ['name' => 'Grade 5', 'code' => 'GR5', 'position' => 5],
            ['name' => 'Grade 6', 'code' => 'GR6', 'position' => 6],
        ];
        
        foreach ($coursesData as $courseData) {
            $existing = DB::table('courses')
                ->where('team_id', $this->teamId)
                ->where('name', $courseData['name'])
                ->first();
            
            if (!$existing) {
                $courseId = DB::table('courses')->insertGetId([
                    'uuid' => \Illuminate\Support\Str::uuid(),
                    'team_id' => $this->teamId,
                    'name' => $courseData['name'],
                    'code' => $courseData['code'],
                    'position' => $courseData['position'],
                    'enable_registration' => true,
                    'has_batch' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->courseIds[$courseData['name']] = $courseId;
                echo "   ✓ Created: {$courseData['name']}\n";
            } else {
                $this->courseIds[$courseData['name']] = $existing->id;
                echo "   • Using existing: {$courseData['name']}\n";
            }
        }
    }
    
    private function createBatches()
    {
        echo "\n🎓 Creating/Checking Batches...\n";
        
        if (empty($this->courseIds)) {
            echo "   ⚠ No courses available. Skipping batches.\n";
            return;
        }
        
        $batchSuffixes = ['A', 'B'];
        
        foreach ($this->courseIds as $courseName => $courseId) {
            foreach ($batchSuffixes as $suffix) {
                $batchName = "Section {$suffix}";
                $batchCode = str_replace(' ', '', $courseName) . "-{$suffix}";
                
                $existing = DB::table('batches')
                    ->where('course_id', $courseId)
                    ->where('name', $batchName)
                    ->first();
                
                if (!$existing) {
                    $batchId = DB::table('batches')->insertGetId([
                        'uuid' => \Illuminate\Support\Str::uuid(),
                        'course_id' => $courseId,
                        'name' => $batchName,
                        'code' => $batchCode,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $this->batchIds[] = $batchId;
                    echo "   ✓ Created: {$courseName} - {$batchName}\n";
                } else {
                    $this->batchIds[] = $existing->id;
                    echo "   • Using existing: {$courseName} - {$batchName}\n";
                }
            }
        }
    }
    
    private function createEmployeeData()
    {
        echo "\n👥 Creating Employee Demo Data...\n";
        
        $employees = [
            [
                'user_email' => 'principal@demo.com',
                'name' => 'Principal Demo',
                'designation' => 'Principal',
                'code' => 'EMP001',
            ],
            [
                'user_email' => 'manager@demo.com',
                'name' => 'Manager Demo',
                'designation' => 'Vice Principal',
                'code' => 'EMP002',
            ],
            [
                'user_email' => 'staff@demo.com',
                'name' => 'Staff Member',
                'designation' => 'Teacher',
                'code' => 'EMP003',
            ],
            [
                'user_email' => 'accountant@demo.com',
                'name' => 'John Accountant',
                'designation' => 'Accountant',
                'code' => 'EMP004',
            ],
            [
                'user_email' => 'librarian@demo.com',
                'name' => 'Sarah Librarian',
                'designation' => 'Librarian',
                'code' => 'EMP005',
            ],
            [
                'user_email' => 'exam@demo.com',
                'name' => 'Mike Exam Coordinator',
                'designation' => 'Exam Coordinator',
                'code' => 'EMP006',
            ],
            [
                'user_email' => 'transport@demo.com',
                'name' => 'David Transport Manager',
                'designation' => 'Transport Manager',
                'code' => 'EMP007',
            ],
        ];
        
        foreach ($employees as $emp) {
            // Get user
            $user = DB::table('users')->where('email', $emp['user_email'])->first();
            if (!$user) {
                echo "   ⚠ User not found: {$emp['user_email']}\n";
                continue;
            }
            
            // Check if contact already exists
            $contact = DB::table('contacts')->where('user_id', $user->id)->first();
            
            if (!$contact) {
                // Create contact
                $contactId = DB::table('contacts')->insertGetId([
                    'uuid' => \Illuminate\Support\Str::uuid(),
                    'team_id' => $this->teamId,
                    'user_id' => $user->id,
                    'first_name' => explode(' ', $emp['name'])[0],
                    'last_name' => explode(' ', $emp['name'])[1] ?? '',
                    'gender' => 'male',
                    'contact_number' => '555-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT),
                    'email' => $emp['user_email'],
                    'birth_date' => Carbon::now()->subYears(rand(30, 50))->format('Y-m-d'),
                    'meta' => json_encode(['source' => 'employee']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                echo "   ✓ Created contact for: {$emp['name']}\n";
            } else {
                $contactId = $contact->id;
                echo "   • Using existing contact for: {$emp['name']}\n";
            }
            
            // Check if employee record exists
            $employee = DB::table('employees')->where('contact_id', $contactId)->first();
            
            if (!$employee) {
                // Create employee
                $employeeId = DB::table('employees')->insertGetId([
                    'uuid' => \Illuminate\Support\Str::uuid(),
                    'team_id' => $this->teamId,
                    'contact_id' => $contactId,
                    'code_number' => $emp['code'],
                    'joining_date' => Carbon::now()->subYears(rand(1, 5))->format('Y-m-d'),
                    'type' => 'permanent',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // Create employee record (designation & department assignment)
                $deptId = null;
                if ($emp['designation'] == 'Principal' || $emp['designation'] == 'Vice Principal') {
                    $deptId = $this->departmentIds['Administration'];
                } elseif ($emp['designation'] == 'Teacher' || $emp['designation'] == 'Senior Teacher') {
                    $deptId = $this->departmentIds['Academics'];
                } elseif ($emp['designation'] == 'Accountant') {
                    $deptId = $this->departmentIds['Accounts'];
                } elseif ($emp['designation'] == 'Librarian') {
                    $deptId = $this->departmentIds['Library'];
                } elseif ($emp['designation'] == 'Transport Manager') {
                    $deptId = $this->departmentIds['Transport'];
                } elseif ($emp['designation'] == 'Exam Coordinator') {
                    $deptId = $this->departmentIds['Examination'];
                }
                
                DB::table('employee_records')->insert([
                    'uuid' => \Illuminate\Support\Str::uuid(),
                    'employee_id' => $employeeId,
                    'department_id' => $deptId,
                    'designation_id' => $this->designationIds[$emp['designation']],
                    'start_date' => Carbon::now()->subYears(rand(1, 5))->format('Y-m-d'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                echo "   ✓ Created employee record: {$emp['name']} - {$emp['designation']}\n";
            } else {
                echo "   • Employee record already exists for: {$emp['name']}\n";
            }
        }
    }
    
    private function createStudentData()
    {
        echo "\n🎒 Creating Student Demo Data...\n";
        
        // Get student user
        $studentUser = DB::table('users')->where('email', 'student@demo.com')->first();
        if (!$studentUser) {
            echo "   ⚠ Student user not found\n";
            return;
        }
        
        // Check if contact exists
        $contact = DB::table('contacts')->where('user_id', $studentUser->id)->first();
        
        if (!$contact) {
            // Create student contact
            $contactId = DB::table('contacts')->insertGetId([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'team_id' => $this->teamId,
                'user_id' => $studentUser->id,
                'first_name' => 'Tom',
                'last_name' => 'Student',
                'father_name' => 'Mr. Student Senior',
                'mother_name' => 'Mrs. Student',
                'gender' => 'male',
                'contact_number' => '555-1234',
                'email' => 'student@demo.com',
                'birth_date' => Carbon::now()->subYears(10)->format('Y-m-d'),
                'meta' => json_encode(['source' => 'student']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "   ✓ Created student contact\n";
        } else {
            $contactId = $contact->id;
            echo "   • Using existing student contact\n";
        }
        
        // Check if registration exists
        $registration = DB::table('registrations')->where('contact_id', $contactId)->first();
        
        // Create registration
        if (!$registration) {
            $firstCourse = reset($this->courseIds);
            if (!$firstCourse) {
                echo "   ⚠ No courses available. Cannot create registration.\n";
                return;
            }
            
            // Create registration
            $registrationId = DB::table('registrations')->insertGetId([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'team_id' => $this->teamId,
                'contact_id' => $contactId,
                'period_id' => $this->periodId,
                'course_id' => $firstCourse,
                'code_number' => 'REG-' . date('Y') . '-001',
                'date' => Carbon::now()->subMonths(6)->format('Y-m-d'),
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "   ✓ Created registration\n";
        } else {
            $registrationId = $registration->id;
            echo "   • Using existing registration\n";
        }
        
        // Check if admission exists
        $admission = DB::table('admissions')->where('registration_id', $registrationId)->first();
        
        if (!$admission) {
            // Create admission
            $admissionId = DB::table('admissions')->insertGetId([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'registration_id' => $registrationId,
                'code_number' => 'ADM-' . date('Y') . '-001',
                'joining_date' => Carbon::now()->subMonths(5)->format('Y-m-d'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "   ✓ Created admission\n";
        } else {
            $admissionId = $admission->id;
            echo "   • Using existing admission\n";
        }
        
        // Check if student record exists
        $student = DB::table('students')->where('contact_id', $contactId)->first();
        
        if (!$student) {
            $firstBatch = reset($this->batchIds);
            if (!$firstBatch) {
                echo "   ⚠ No batches available. Cannot create student record.\n";
                return;
            }
            
            // Create student
            DB::table('students')->insert([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'admission_id' => $admissionId,
                'period_id' => $this->periodId,
                'batch_id' => $firstBatch,
                'contact_id' => $contactId,
                'roll_number' => '001',
                'start_date' => Carbon::now()->subMonths(5)->format('Y-m-d'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "   ✓ Created student record\n";
        } else {
            echo "   • Student record already exists\n";
        }
    }
    
    private function createGuardianData()
    {
        echo "\n👪 Creating Guardian Demo Data...\n";
        
        // Get guardian user
        $guardianUser = DB::table('users')->where('email', 'parent@demo.com')->first();
        if (!$guardianUser) {
            echo "   ⚠ Guardian user not found\n";
            return;
        }
        
        // Get student contact
        $studentContact = DB::table('contacts')
            ->join('users', 'contacts.user_id', '=', 'users.id')
            ->where('users.email', 'student@demo.com')
            ->select('contacts.*')
            ->first();
            
        if (!$studentContact) {
            echo "   ⚠ Student contact not found\n";
            return;
        }
        
        // Check if guardian contact exists
        $guardianContact = DB::table('contacts')->where('user_id', $guardianUser->id)->first();
        
        if (!$guardianContact) {
            // Create guardian contact
            $guardianContactId = DB::table('contacts')->insertGetId([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'team_id' => $this->teamId,
                'user_id' => $guardianUser->id,
                'first_name' => 'Parent',
                'last_name' => 'Guardian',
                'gender' => 'male',
                'contact_number' => '555-5678',
                'email' => 'parent@demo.com',
                'birth_date' => Carbon::now()->subYears(40)->format('Y-m-d'),
                'occupation' => 'Business Owner',
                'meta' => json_encode(['source' => 'guardian']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "   ✓ Created guardian contact\n";
        } else {
            $guardianContactId = $guardianContact->id;
            echo "   • Using existing guardian contact\n";
        }
        
        // Check if guardian relationship exists
        $guardianRelation = DB::table('guardians')
            ->where('primary_contact_id', $studentContact->id)
            ->where('contact_id', $guardianContactId)
            ->first();
            
        if (!$guardianRelation) {
            // Create guardian relationship
            DB::table('guardians')->insert([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'primary_contact_id' => $studentContact->id,
                'contact_id' => $guardianContactId,
                'relation' => 'father',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "   ✓ Created guardian relationship\n";
        } else {
            echo "   • Guardian relationship already exists\n";
        }
    }
}
