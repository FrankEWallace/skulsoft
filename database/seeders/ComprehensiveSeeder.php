<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ComprehensiveSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This seeder creates a complete working environment for the school management system
     */
    public function run()
    {
        $this->command->info('Starting comprehensive database seeding...');

        // 1. Fix existing data first
        $this->fixExistingData();

        // 2. Create basic options (if not exists)
        $this->createOptions();

        // 3. Create academic structure
        $this->createAcademicStructure();

        // 4. Create employees (teachers, staff)
        $this->createEmployees();

        // 5. Create students
        $this->createStudents();

        // 6. Create finance structure
        $this->createFinanceStructure();

        // 7. Create library data
        $this->createLibraryData();

        // 8. Create additional modules data
        $this->createAdditionalData();

        $this->command->info('✅ Comprehensive seeding completed successfully!');
    }

    /**
     * Fix existing data issues
     */
    protected function fixExistingData()
    {
        $this->command->info('Fixing existing data...');

        // Fix admin role - set team_id
        DB::table('roles')->where('id', 1)->update(['team_id' => 1]);
        
        // Ensure admin user has correct meta
        $user = DB::table('users')->where('id', 1)->first();
        if ($user) {
            $meta = json_decode($user->meta ?? '{}', true);
            $meta['current_team_id'] = 1;
            $meta['current_period_id'] = 1;
            $meta['is_default'] = true;
            
            DB::table('users')->where('id', 1)->update([
                'meta' => json_encode($meta),
                'password' => Hash::make('admin123'), // Reset password to known value
                'updated_at' => now(),
            ]);
        }

        // Ensure model_has_roles has team_id
        DB::table('model_has_roles')
            ->where('model_id', 1)
            ->where('role_id', 1)
            ->update(['team_id' => 1]);

        $this->command->info('✓ Fixed existing data');
    }

    /**
     * Create essential options
     */
    protected function createOptions()
    {
        $this->command->info('Creating options...');

        $team_id = 1;
        $options = [
            // Blood Groups
            ['team_id' => $team_id, 'type' => 'blood_group', 'name' => 'A+', 'slug' => 'a-positive'],
            ['team_id' => $team_id, 'type' => 'blood_group', 'name' => 'A-', 'slug' => 'a-negative'],
            ['team_id' => $team_id, 'type' => 'blood_group', 'name' => 'B+', 'slug' => 'b-positive'],
            ['team_id' => $team_id, 'type' => 'blood_group', 'name' => 'B-', 'slug' => 'b-negative'],
            ['team_id' => $team_id, 'type' => 'blood_group', 'name' => 'O+', 'slug' => 'o-positive'],
            ['team_id' => $team_id, 'type' => 'blood_group', 'name' => 'O-', 'slug' => 'o-negative'],
            ['team_id' => $team_id, 'type' => 'blood_group', 'name' => 'AB+', 'slug' => 'ab-positive'],
            ['team_id' => $team_id, 'type' => 'blood_group', 'name' => 'AB-', 'slug' => 'ab-negative'],
            
            // Religions
            ['team_id' => $team_id, 'type' => 'religion', 'name' => 'Christianity', 'slug' => 'christianity'],
            ['team_id' => $team_id, 'type' => 'religion', 'name' => 'Islam', 'slug' => 'islam'],
            ['team_id' => $team_id, 'type' => 'religion', 'name' => 'Hinduism', 'slug' => 'hinduism'],
            ['team_id' => $team_id, 'type' => 'religion', 'name' => 'Buddhism', 'slug' => 'buddhism'],
            ['team_id' => $team_id, 'type' => 'religion', 'name' => 'Other', 'slug' => 'other'],
            
            // Categories
            ['team_id' => $team_id, 'type' => 'category', 'name' => 'General', 'slug' => 'general'],
            ['team_id' => $team_id, 'type' => 'category', 'name' => 'OBC', 'slug' => 'obc'],
            ['team_id' => $team_id, 'type' => 'category', 'name' => 'SC', 'slug' => 'sc'],
            ['team_id' => $team_id, 'type' => 'category', 'name' => 'ST', 'slug' => 'st'],
            
            // Caste
            ['team_id' => $team_id, 'type' => 'caste', 'name' => 'Not Applicable', 'slug' => 'not-applicable'],
        ];

        foreach ($options as $option) {
            DB::table('options')->updateOrInsert(
                ['type' => $option['type'], 'slug' => $option['slug'], 'team_id' => $team_id],
                array_merge($option, [
                    'uuid' => Str::uuid(),
                    'meta' => '{}',
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('✓ Created options');
    }

    /**
     * Create academic structure (departments, programs, batches, subjects)
     */
    protected function createAcademicStructure()
    {
        $this->command->info('Creating academic structure...');

        $team_id = 1;
        $period_id = 1;

        // Create departments
        $departments = [
            ['name' => 'Science', 'alias' => 'science', 'code' => 'SCI', 'description' => 'Science Department'],
            ['name' => 'Commerce', 'alias' => 'commerce', 'code' => 'COM', 'description' => 'Commerce Department'],
            ['name' => 'Arts', 'alias' => 'arts', 'code' => 'ART', 'description' => 'Arts Department'],
        ];

        foreach ($departments as $dept) {
            DB::table('academic_departments')->updateOrInsert(
                ['alias' => $dept['alias'], 'team_id' => $team_id],
                array_merge($dept, [
                    'uuid' => Str::uuid(),
                    'team_id' => $team_id,
                    'meta' => '{}',
                    'config' => '{}',
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // Create program type
        $programTypeId = DB::table('program_types')->insertGetId([
            'uuid' => Str::uuid(),
            'team_id' => $team_id,
            'name' => 'Secondary Education',
            'slug' => 'secondary-education',
            'description' => 'Grades 1-12',
            'meta' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create programs (grades)
        $programs = [];
        for ($i = 1; $i <= 12; $i++) {
            $programId = DB::table('programs')->insertGetId([
                'uuid' => Str::uuid(),
                'team_id' => $team_id,
                'program_type_id' => $programTypeId,
                'name' => 'Grade ' . $i,
                'slug' => 'grade-' . $i,
                'description' => 'Grade ' . $i,
                'meta' => '{}',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $programs[$i] = $programId;
        }

        // Create courses for each program
        foreach ($programs as $grade => $programId) {
            $courseId = DB::table('courses')->insertGetId([
                'uuid' => Str::uuid(),
                'team_id' => $team_id,
                'program_id' => $programId,
                'period_id' => $period_id,
                'name' => 'Standard Course',
                'slug' => 'standard-course-grade-' . $grade,
                'description' => 'Standard course for Grade ' . $grade,
                'meta' => '{}',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create batch for each course
            DB::table('batches')->insert([
                'uuid' => Str::uuid(),
                'team_id' => $team_id,
                'course_id' => $courseId,
                'name' => 'Section A',
                'slug' => 'section-a-grade-' . $grade,
                'description' => 'Section A for Grade ' . $grade,
                'meta' => '{}',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create subjects
        $subjects = [
            ['name' => 'Mathematics', 'slug' => 'mathematics', 'code' => 'MATH'],
            ['name' => 'English', 'slug' => 'english', 'code' => 'ENG'],
            ['name' => 'Science', 'slug' => 'science', 'code' => 'SCI'],
            ['name' => 'Social Studies', 'slug' => 'social-studies', 'code' => 'SS'],
            ['name' => 'Physical Education', 'slug' => 'physical-education', 'code' => 'PE'],
        ];

        foreach ($subjects as $subject) {
            DB::table('subjects')->updateOrInsert(
                ['slug' => $subject['slug'], 'team_id' => $team_id],
                array_merge($subject, [
                    'uuid' => Str::uuid(),
                    'team_id' => $team_id,
                    'meta' => '{}',
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('✓ Created academic structure');
    }

    /**
     * Create employee data
     */
    protected function createEmployees()
    {
        $this->command->info('Creating employees...');

        $team_id = 1;

        // Create departments
        $deptId = DB::table('departments')->insertGetId([
            'uuid' => Str::uuid(),
            'team_id' => $team_id,
            'name' => 'Teaching Staff',
            'slug' => 'teaching-staff',
            'description' => 'All teaching staff',
            'meta' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create designations
        $designations = [
            ['name' => 'Principal', 'slug' => 'principal'],
            ['name' => 'Vice Principal', 'slug' => 'vice-principal'],
            ['name' => 'Senior Teacher', 'slug' => 'senior-teacher'],
            ['name' => 'Teacher', 'slug' => 'teacher'],
            ['name' => 'Assistant Teacher', 'slug' => 'assistant-teacher'],
        ];

        $designationIds = [];
        foreach ($designations as $designation) {
            $designationIds[$designation['slug']] = DB::table('designations')->insertGetId(
                array_merge($designation, [
                    'uuid' => Str::uuid(),
                    'team_id' => $team_id,
                    'description' => $designation['name'],
                    'meta' => '{}',
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // Create sample employees
        $employees = [
            [
                'code_number' => 'EMP001',
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'john.smith@school.com',
                'designation_id' => $designationIds['principal'],
                'role' => 'principal',
            ],
            [
                'code_number' => 'EMP002',
                'first_name' => 'Sarah',
                'last_name' => 'Johnson',
                'email' => 'sarah.johnson@school.com',
                'designation_id' => $designationIds['teacher'],
                'role' => 'staff',
            ],
            [
                'code_number' => 'EMP003',
                'first_name' => 'Michael',
                'last_name' => 'Williams',
                'email' => 'michael.williams@school.com',
                'designation_id' => $designationIds['teacher'],
                'role' => 'staff',
            ],
        ];

        foreach ($employees as $emp) {
            $employeeId = DB::table('employees')->insertGetId([
                'uuid' => Str::uuid(),
                'team_id' => $team_id,
                'code_number' => $emp['code_number'],
                'joining_date' => now()->subYears(rand(1, 5)),
                'meta' => '{}',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create user for employee
            $userId = DB::table('users')->insertGetId([
                'uuid' => Str::uuid(),
                'name' => $emp['first_name'] . ' ' . $emp['last_name'],
                'email' => $emp['email'],
                'username' => $emp['code_number'],
                'password' => Hash::make('password123'),
                'status' => 'activated',
                'meta' => json_encode([
                    'current_team_id' => $team_id,
                    'current_period_id' => 1,
                ]),
                'preference' => '{}',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Link employee to user
            DB::table('employee_records')->insert([
                'uuid' => Str::uuid(),
                'employee_id' => $employeeId,
                'department_id' => $deptId,
                'designation_id' => $emp['designation_id'],
                'user_id' => $userId,
                'first_name' => $emp['first_name'],
                'last_name' => $emp['last_name'],
                'contact_number' => '+1234567890',
                'gender' => rand(0, 1) ? 'male' : 'female',
                'meta' => '{}',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Assign role to user
            $roleId = DB::table('roles')->where('name', $emp['role'])->where('team_id', $team_id)->value('id');
            if ($roleId) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $roleId,
                    'model_type' => 'User',
                    'model_id' => $userId,
                    'team_id' => $team_id,
                ]);
            }
        }

        $this->command->info('✓ Created employees');
    }

    /**
     * Create student data
     */
    protected function createStudents()
    {
        $this->command->info('Creating students...');

        $team_id = 1;
        $period_id = 1;

        // Get a batch to assign students
        $batch = DB::table('batches')->where('team_id', $team_id)->first();
        if (!$batch) {
            $this->command->warn('No batch found, skipping student creation');
            return;
        }

        // Create sample students
        $students = [
            ['first_name' => 'Alice', 'last_name' => 'Brown', 'email' => 'alice.brown@student.com'],
            ['first_name' => 'Bob', 'last_name' => 'Davis', 'email' => 'bob.davis@student.com'],
            ['first_name' => 'Charlie', 'last_name' => 'Wilson', 'email' => 'charlie.wilson@student.com'],
            ['first_name' => 'Diana', 'last_name' => 'Moore', 'email' => 'diana.moore@student.com'],
            ['first_name' => 'Eve', 'last_name' => 'Taylor', 'email' => 'eve.taylor@student.com'],
        ];

        foreach ($students as $index => $stud) {
            $studentId = DB::table('students')->insertGetId([
                'uuid' => Str::uuid(),
                'team_id' => $team_id,
                'batch_id' => $batch->id,
                'code_number' => 'STU' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'joining_date' => now()->subMonths(rand(1, 12)),
                'meta' => '{}',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create user for student
            $userId = DB::table('users')->insertGetId([
                'uuid' => Str::uuid(),
                'name' => $stud['first_name'] . ' ' . $stud['last_name'],
                'email' => $stud['email'],
                'username' => 'STU' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'password' => Hash::make('student123'),
                'status' => 'activated',
                'meta' => json_encode([
                    'current_team_id' => $team_id,
                    'current_period_id' => $period_id,
                ]),
                'preference' => '{}',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update student with user_id and contact
            DB::table('students')->where('id', $studentId)->update([
                'user_id' => $userId,
                'contact_number' => '+1234567' . str_pad($index, 3, '0', STR_PAD_LEFT),
                'first_name' => $stud['first_name'],
                'last_name' => $stud['last_name'],
                'gender' => rand(0, 1) ? 'male' : 'female',
                'birth_date' => now()->subYears(rand(10, 18))->format('Y-m-d'),
                'updated_at' => now(),
            ]);

            // Assign student role
            $roleId = DB::table('roles')->where('name', 'student')->where('team_id', $team_id)->value('id');
            if ($roleId) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $roleId,
                    'model_type' => 'User',
                    'model_id' => $userId,
                    'team_id' => $team_id,
                ]);
            }
        }

        $this->command->info('✓ Created students');
    }

    /**
     * Create finance structure
     */
    protected function createFinanceStructure()
    {
        $this->command->info('Creating finance structure...');

        $team_id = 1;

        // Create payment methods
        $paymentMethods = [
            ['name' => 'Cash', 'slug' => 'cash'],
            ['name' => 'Bank Transfer', 'slug' => 'bank-transfer'],
            ['name' => 'Online Payment', 'slug' => 'online-payment'],
            ['name' => 'Cheque', 'slug' => 'cheque'],
        ];

        foreach ($paymentMethods as $method) {
            DB::table('payment_methods')->updateOrInsert(
                ['slug' => $method['slug'], 'team_id' => $team_id],
                array_merge($method, [
                    'uuid' => Str::uuid(),
                    'team_id' => $team_id,
                    'description' => $method['name'],
                    'meta' => '{}',
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // Create fee groups
        $feeGroupId = DB::table('fee_groups')->insertGetId([
            'uuid' => Str::uuid(),
            'team_id' => $team_id,
            'name' => 'Tuition Fee',
            'slug' => 'tuition-fee',
            'description' => 'Regular tuition fee',
            'meta' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create fee heads
        $feeHeads = [
            ['name' => 'Tuition Fee', 'slug' => 'tuition-fee'],
            ['name' => 'Library Fee', 'slug' => 'library-fee'],
            ['name' => 'Lab Fee', 'slug' => 'lab-fee'],
            ['name' => 'Sports Fee', 'slug' => 'sports-fee'],
        ];

        foreach ($feeHeads as $head) {
            DB::table('fee_heads')->updateOrInsert(
                ['slug' => $head['slug'], 'team_id' => $team_id],
                array_merge($head, [
                    'uuid' => Str::uuid(),
                    'team_id' => $team_id,
                    'fee_group_id' => $feeGroupId,
                    'description' => $head['name'],
                    'meta' => '{}',
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('✓ Created finance structure');
    }

    /**
     * Create library data
     */
    protected function createLibraryData()
    {
        $this->command->info('Creating library data...');

        $team_id = 1;

        // Create sample books
        $books = [
            ['title' => 'Introduction to Mathematics', 'author' => 'John Doe', 'isbn' => '978-0-123456-78-9'],
            ['title' => 'English Grammar', 'author' => 'Jane Smith', 'isbn' => '978-0-234567-89-0'],
            ['title' => 'Science for Beginners', 'author' => 'Robert Brown', 'isbn' => '978-0-345678-90-1'],
            ['title' => 'World History', 'author' => 'Mary Johnson', 'isbn' => '978-0-456789-01-2'],
            ['title' => 'Physical Education Guide', 'author' => 'James Wilson', 'isbn' => '978-0-567890-12-3'],
        ];

        foreach ($books as $book) {
            $bookId = DB::table('books')->insertGetId([
                'uuid' => Str::uuid(),
                'team_id' => $team_id,
                'title' => $book['title'],
                'slug' => Str::slug($book['title']),
                'author' => $book['author'],
                'isbn' => $book['isbn'],
                'publisher' => 'Educational Publishers',
                'page_count' => rand(100, 500),
                'meta' => '{}',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create book copies
            for ($i = 1; $i <= 3; $i++) {
                DB::table('book_copies')->insert([
                    'uuid' => Str::uuid(),
                    'team_id' => $team_id,
                    'book_id' => $bookId,
                    'number' => 'COPY-' . $bookId . '-' . $i,
                    'status' => 'available',
                    'meta' => '{}',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('✓ Created library data');
    }

    /**
     * Create additional module data
     */
    protected function createAdditionalData()
    {
        $this->command->info('Creating additional data...');

        $team_id = 1;

        // Create attendance types for employees
        $attendanceTypes = [
            ['name' => 'Present', 'slug' => 'present', 'code' => 'P', 'color' => 'green'],
            ['name' => 'Absent', 'slug' => 'absent', 'code' => 'A', 'color' => 'red'],
            ['name' => 'Late', 'slug' => 'late', 'code' => 'L', 'color' => 'orange'],
            ['name' => 'Half Day', 'slug' => 'half-day', 'code' => 'HD', 'color' => 'yellow'],
        ];

        foreach ($attendanceTypes as $type) {
            DB::table('attendance_types')->updateOrInsert(
                ['slug' => $type['slug'], 'team_id' => $team_id],
                array_merge($type, [
                    'uuid' => Str::uuid(),
                    'team_id' => $team_id,
                    'description' => $type['name'],
                    'meta' => '{}',
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // Create exam terms
        $examTerms = [
            ['name' => 'First Term', 'slug' => 'first-term'],
            ['name' => 'Mid Term', 'slug' => 'mid-term'],
            ['name' => 'Final Term', 'slug' => 'final-term'],
        ];

        foreach ($examTerms as $term) {
            DB::table('exam_terms')->updateOrInsert(
                ['slug' => $term['slug'], 'team_id' => $team_id],
                array_merge($term, [
                    'uuid' => Str::uuid(),
                    'team_id' => $team_id,
                    'description' => $term['name'],
                    'meta' => '{}',
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('✓ Created additional data');
    }
}
