<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StudentDemoDataSeeder extends Seeder
{
    private $teamId = 1;
    private $divisionId = 1;
    private $periodId;
    private $batchIds = [];
    private $courseIds = [];
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "\n🚀 Starting Comprehensive Student Demo Data Seeding...\n\n";
        
        // Get or create period
        $this->periodId = $this->getOrCreatePeriod();
        
        // Create courses and batches
        $this->createCourses();
        $this->createBatches();
        
        // Create student demo data
        $this->createStudentData();
        
        echo "\n✅ Student Demo Data Seeding Complete!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    }
    
    private function getOrCreatePeriod()
    {
        $period = DB::table('periods')
            ->where('team_id', $this->teamId)
            ->where('name', '2026-2027')
            ->first();
        
        if (!$period) {
            $periodId = DB::table('periods')->insertGetId([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'team_id' => $this->teamId,
                'name' => '2026-2027',
                'start_date' => '2026-04-01',
                'end_date' => '2027-03-31',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return $periodId;
        }
        
        return $period->id;
    }
    
    private function createCourses()
    {
        echo "\n📚 Creating/Checking Courses...\n";
        
        // Check or create division first
        $division = DB::table('divisions')->first();
        if (!$division) {
            $divisionId = DB::table('divisions')->insertGetId([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'name' => 'Primary Section',
                'code' => 'PRIMARY',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->divisionId = $divisionId;
            echo "   ✓ Created division: Primary Section\n";
        } else {
            $this->divisionId = $division->id;
            echo "   • Using existing division: {$division->name}\n";
        }
        
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
                ->where('name', $courseData['name'])
                ->first();
            
            if (!$existing) {
                $courseId = DB::table('courses')->insertGetId([
                    'uuid' => \Illuminate\Support\Str::uuid(),
                    'division_id' => $this->divisionId,
                    'name' => $courseData['name'],
                    'code' => $courseData['code'],
                    'position' => $courseData['position'],
                    'enable_registration' => true,
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
                
                $existing = DB::table('batches')
                    ->where('course_id', $courseId)
                    ->where('name', $batchName)
                    ->first();
                
                if (!$existing) {
                    $batchId = DB::table('batches')->insertGetId([
                        'uuid' => \Illuminate\Support\Str::uuid(),
                        'course_id' => $courseId,
                        'name' => $batchName,
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
    
    private function createStudentData()
    {
        echo "\n👨‍🎓 Creating Student Demo Data...\n";
        
        if (empty($this->courseIds) || empty($this->batchIds)) {
            echo "   ⚠ No courses or batches available. Skipping students.\n";
            return;
        }
        
        $studentsData = [
            // Grade 1 Students
            [
                'first_name' => 'Emma',
                'last_name' => 'Johnson',
                'gender' => 'female',
                'father_name' => 'Robert Johnson',
                'mother_name' => 'Mary Johnson',
                'contact_number' => '555-1001',
                'email' => 'emma.johnson@demo.com',
                'birth_date' => '2020-03-15',
                'blood_group' => 'A+',
                'course' => 'Grade 1',
                'batch_index' => 0, // Section A
                'roll_number' => '001',
                'admission_number' => 'ADM2026001',
                'address' => '123 Oak Street',
                'city' => 'Springfield',
                'state' => 'Illinois',
                'zipcode' => '62701',
            ],
            [
                'first_name' => 'Liam',
                'last_name' => 'Smith',
                'gender' => 'male',
                'father_name' => 'James Smith',
                'mother_name' => 'Patricia Smith',
                'contact_number' => '555-1002',
                'email' => 'liam.smith@demo.com',
                'birth_date' => '2020-05-22',
                'blood_group' => 'B+',
                'course' => 'Grade 1',
                'batch_index' => 0,
                'roll_number' => '002',
                'admission_number' => 'ADM2026002',
                'address' => '456 Maple Avenue',
                'city' => 'Springfield',
                'state' => 'Illinois',
                'zipcode' => '62702',
            ],
            [
                'first_name' => 'Olivia',
                'last_name' => 'Williams',
                'gender' => 'female',
                'father_name' => 'Michael Williams',
                'mother_name' => 'Linda Williams',
                'contact_number' => '555-1003',
                'email' => 'olivia.williams@demo.com',
                'birth_date' => '2020-01-10',
                'blood_group' => 'O+',
                'course' => 'Grade 1',
                'batch_index' => 1, // Section B
                'roll_number' => '001',
                'admission_number' => 'ADM2026003',
                'address' => '789 Pine Road',
                'city' => 'Springfield',
                'state' => 'Illinois',
                'zipcode' => '62703',
            ],
            
            // Grade 2 Students
            [
                'first_name' => 'Noah',
                'last_name' => 'Brown',
                'gender' => 'male',
                'father_name' => 'David Brown',
                'mother_name' => 'Jennifer Brown',
                'contact_number' => '555-1004',
                'email' => 'noah.brown@demo.com',
                'birth_date' => '2019-08-18',
                'blood_group' => 'AB+',
                'course' => 'Grade 2',
                'batch_index' => 2, // Section A
                'roll_number' => '001',
                'admission_number' => 'ADM2025001',
                'address' => '321 Elm Street',
                'city' => 'Springfield',
                'state' => 'Illinois',
                'zipcode' => '62704',
            ],
            [
                'first_name' => 'Ava',
                'last_name' => 'Jones',
                'gender' => 'female',
                'father_name' => 'William Jones',
                'mother_name' => 'Elizabeth Jones',
                'contact_number' => '555-1005',
                'email' => 'ava.jones@demo.com',
                'birth_date' => '2019-11-30',
                'blood_group' => 'A-',
                'course' => 'Grade 2',
                'batch_index' => 2,
                'roll_number' => '002',
                'admission_number' => 'ADM2025002',
                'address' => '654 Birch Lane',
                'city' => 'Springfield',
                'state' => 'Illinois',
                'zipcode' => '62705',
            ],
            
            // Grade 3 Students
            [
                'first_name' => 'Ethan',
                'last_name' => 'Garcia',
                'gender' => 'male',
                'father_name' => 'Carlos Garcia',
                'mother_name' => 'Maria Garcia',
                'contact_number' => '555-1006',
                'email' => 'ethan.garcia@demo.com',
                'birth_date' => '2018-04-25',
                'blood_group' => 'B-',
                'course' => 'Grade 3',
                'batch_index' => 4, // Section A
                'roll_number' => '001',
                'admission_number' => 'ADM2024001',
                'address' => '987 Cedar Court',
                'city' => 'Springfield',
                'state' => 'Illinois',
                'zipcode' => '62706',
            ],
            [
                'first_name' => 'Sophia',
                'last_name' => 'Martinez',
                'gender' => 'female',
                'father_name' => 'Jose Martinez',
                'mother_name' => 'Carmen Martinez',
                'contact_number' => '555-1007',
                'email' => 'sophia.martinez@demo.com',
                'birth_date' => '2018-09-12',
                'blood_group' => 'O-',
                'course' => 'Grade 3',
                'batch_index' => 4,
                'roll_number' => '002',
                'admission_number' => 'ADM2024002',
                'address' => '147 Willow Drive',
                'city' => 'Springfield',
                'state' => 'Illinois',
                'zipcode' => '62707',
            ],
            
            // Grade 4 Students
            [
                'first_name' => 'Mason',
                'last_name' => 'Rodriguez',
                'gender' => 'male',
                'father_name' => 'Juan Rodriguez',
                'mother_name' => 'Rosa Rodriguez',
                'contact_number' => '555-1008',
                'email' => 'mason.rodriguez@demo.com',
                'birth_date' => '2017-02-28',
                'blood_group' => 'A+',
                'course' => 'Grade 4',
                'batch_index' => 6, // Section A
                'roll_number' => '001',
                'admission_number' => 'ADM2023001',
                'address' => '258 Spruce Avenue',
                'city' => 'Springfield',
                'state' => 'Illinois',
                'zipcode' => '62708',
            ],
            [
                'first_name' => 'Isabella',
                'last_name' => 'Davis',
                'gender' => 'female',
                'father_name' => 'Thomas Davis',
                'mother_name' => 'Sarah Davis',
                'contact_number' => '555-1009',
                'email' => 'isabella.davis@demo.com',
                'birth_date' => '2017-07-14',
                'blood_group' => 'B+',
                'course' => 'Grade 4',
                'batch_index' => 6,
                'roll_number' => '002',
                'admission_number' => 'ADM2023002',
                'address' => '369 Ash Boulevard',
                'city' => 'Springfield',
                'state' => 'Illinois',
                'zipcode' => '62709',
            ],
            
            // Grade 5 Students
            [
                'first_name' => 'Lucas',
                'last_name' => 'Miller',
                'gender' => 'male',
                'father_name' => 'Daniel Miller',
                'mother_name' => 'Nancy Miller',
                'contact_number' => '555-1010',
                'email' => 'lucas.miller@demo.com',
                'birth_date' => '2016-12-05',
                'blood_group' => 'O+',
                'course' => 'Grade 5',
                'batch_index' => 8, // Section A
                'roll_number' => '001',
                'admission_number' => 'ADM2022001',
                'address' => '741 Poplar Street',
                'city' => 'Springfield',
                'state' => 'Illinois',
                'zipcode' => '62710',
            ],
            [
                'first_name' => 'Mia',
                'last_name' => 'Wilson',
                'gender' => 'female',
                'father_name' => 'Christopher Wilson',
                'mother_name' => 'Karen Wilson',
                'contact_number' => '555-1011',
                'email' => 'mia.wilson@demo.com',
                'birth_date' => '2016-06-20',
                'blood_group' => 'AB-',
                'course' => 'Grade 5',
                'batch_index' => 8,
                'roll_number' => '002',
                'admission_number' => 'ADM2022002',
                'address' => '852 Hickory Lane',
                'city' => 'Springfield',
                'state' => 'Illinois',
                'zipcode' => '62711',
            ],
            
            // Grade 6 Students
            [
                'first_name' => 'Alexander',
                'last_name' => 'Moore',
                'gender' => 'male',
                'father_name' => 'Richard Moore',
                'mother_name' => 'Betty Moore',
                'contact_number' => '555-1012',
                'email' => 'alexander.moore@demo.com',
                'birth_date' => '2015-10-08',
                'blood_group' => 'A+',
                'course' => 'Grade 6',
                'batch_index' => 10, // Section A
                'roll_number' => '001',
                'admission_number' => 'ADM2021001',
                'address' => '963 Walnut Road',
                'city' => 'Springfield',
                'state' => 'Illinois',
                'zipcode' => '62712',
            ],
            [
                'first_name' => 'Charlotte',
                'last_name' => 'Taylor',
                'gender' => 'female',
                'father_name' => 'Matthew Taylor',
                'mother_name' => 'Margaret Taylor',
                'contact_number' => '555-1013',
                'email' => 'charlotte.taylor@demo.com',
                'birth_date' => '2015-03-16',
                'blood_group' => 'B+',
                'course' => 'Grade 6',
                'batch_index' => 10,
                'roll_number' => '002',
                'admission_number' => 'ADM2021002',
                'address' => '159 Chestnut Court',
                'city' => 'Springfield',
                'state' => 'Illinois',
                'zipcode' => '62713',
            ],
            
            // Existing student@demo.com
            [
                'first_name' => 'Tom',
                'last_name' => 'Student',
                'gender' => 'male',
                'father_name' => 'Mr. Student Senior',
                'mother_name' => 'Mrs. Student',
                'contact_number' => '555-1234',
                'email' => 'student@demo.com',
                'birth_date' => '2016-02-24',
                'blood_group' => 'O+',
                'course' => 'Grade 5',
                'batch_index' => 9, // Section B
                'roll_number' => '001',
                'admission_number' => 'ADM2022003',
                'address' => '555 Demo Street',
                'city' => 'Springfield',
                'state' => 'Illinois',
                'zipcode' => '62714',
            ],
        ];
        
        $createdCount = 0;
        foreach ($studentsData as $studentData) {
            $created = $this->createStudentRecord($studentData);
            if ($created) {
                $createdCount++;
            }
        }
        
        echo "\n   ✅ Total students created/updated: {$createdCount}\n";
    }
    
    private function createStudentRecord($data)
    {
        // Get user for student if exists
        $studentUser = DB::table('users')->where('email', $data['email'])->first();
        
        // Check if contact already exists
        $contact = DB::table('contacts')
            ->where('team_id', $this->teamId)
            ->where('email', $data['email'])
            ->first();
        
        if (!$contact) {
            // Create contact
            $contactId = DB::table('contacts')->insertGetId([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'team_id' => $this->teamId,
                'user_id' => $studentUser->id ?? null,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'father_name' => $data['father_name'],
                'mother_name' => $data['mother_name'],
                'gender' => $data['gender'],
                'contact_number' => $data['contact_number'],
                'email' => $data['email'],
                'birth_date' => $data['birth_date'],
                'address' => json_encode([
                    'address_line1' => $data['address'],
                    'city' => $data['city'],
                    'state' => $data['state'],
                    'zipcode' => $data['zipcode'],
                    'country' => 'USA',
                ]),
                'meta' => json_encode([
                    'source' => 'student',
                    'blood_group' => $data['blood_group'],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "   ✓ Created contact: {$data['first_name']} {$data['last_name']}\n";
        } else {
            $contactId = $contact->id;
            echo "   • Contact exists: {$data['first_name']} {$data['last_name']}\n";
        }
        
        // Check if registration exists
        $registration = DB::table('registrations')->where('contact_id', $contactId)->first();
        
        if (!$registration) {
            $courseId = $this->courseIds[$data['course']];
            
            // Create registration
            $registrationId = DB::table('registrations')->insertGetId([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'contact_id' => $contactId,
                'period_id' => $this->periodId,
                'course_id' => $courseId,
                'code_number' => 'REG-' . $data['admission_number'],
                'date' => Carbon::now()->subMonths(6)->format('Y-m-d'),
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $registrationId = $registration->id;
        }
        
        // Check if admission exists
        $admission = DB::table('admissions')->where('registration_id', $registrationId)->first();
        
        if (!$admission) {
            // Create admission
            $admissionId = DB::table('admissions')->insertGetId([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'registration_id' => $registrationId,
                'code_number' => $data['admission_number'],
                'joining_date' => Carbon::now()->subMonths(5)->format('Y-m-d'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $admissionId = $admission->id;
        }
        
        // Check if student record exists
        $student = DB::table('students')->where('contact_id', $contactId)->first();
        
        if (!$student) {
            $batchId = $this->batchIds[$data['batch_index']];
            
            // Create student
            DB::table('students')->insert([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'admission_id' => $admissionId,
                'period_id' => $this->periodId,
                'batch_id' => $batchId,
                'contact_id' => $contactId,
                'roll_number' => $data['roll_number'],
                'start_date' => Carbon::now()->subMonths(5)->format('Y-m-d'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return true;
        }
        
        return false;
    }
}
