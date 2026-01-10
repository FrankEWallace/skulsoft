# Laravel 11 Framework Restructuring Guide

## Table of Contents
- [Project Overview](#project-overview)
- [Current Structure Analysis](#current-structure-analysis)
- [Recommended Changes](#recommended-changes)
- [Implementation Plan](#implementation-plan)
- [Best Practices](#best-practices)
- [Migration Steps](#migration-steps)

---

<a name="project-overview"></a>
## Project Overview

**Project:** SkulSoft - School Management System  
**Framework:** Laravel 11.x  
**PHP Version:** 8.2+  
**Architecture:** Multi-tenant with Livewire 3  
**Developer:** FW Technologies

### Current Tech Stack
- **Backend:** Laravel 11, PHP 8.2
- **Frontend:** Livewire 3, Vue.js 3, Tailwind CSS
- **Database:** MySQL/MariaDB
- **Packages:** 
  - Spatie Laravel Permission (RBAC)
  - Spatie Activity Log
  - Laravel Horizon (Queue Management)
  - Maatwebsite Excel (Reports)
  - mPDF (PDF Generation)

---

<a name="current-structure-analysis"></a>
## Current Structure Analysis

### ✅ What's Already Good (Laravel 11 Standard)

```
✅ app/
   ├── Models/              # Eloquent models (well organized by domain)
   ├── Http/
   │   ├── Controllers/     # HTTP Controllers
   │   ├── Middleware/      # Custom middleware
   │   ├── Requests/        # Form requests
   │   └── Resources/       # API resources
   ├── Providers/           # Service providers
   ├── Policies/            # Authorization policies
   ├── Events/              # Domain events
   ├── Listeners/           # Event listeners
   ├── Jobs/                # Queue jobs
   ├── Mail/                # Mailable classes
   ├── Notifications/       # Notification classes
   └── Console/             # Artisan commands

✅ routes/
   ├── web.php             # Web routes
   ├── api.php             # API routes
   ├── console.php         # Console routes
   └── channels.php        # Broadcast channels

✅ database/
   ├── migrations/         # Database migrations
   ├── seeders/            # Database seeders
   └── factories/          # Model factories

✅ resources/
   └── views/              # Blade templates

✅ config/                 # Configuration files
✅ storage/                # File storage
✅ public/                 # Public assets
```

### ⚠️ Non-Standard Directories (Need Review)

```
⚠️ app/
   ├── Actions/            # Custom - Could be refactored
   ├── Casts/              # Good - Attribute casting
   ├── Concerns/           # Good - Traits/Mixins
   ├── Contracts/          # Good - Interfaces
   ├── Enums/              # Good - PHP 8.1+ Enums
   ├── Exports/            # Custom - Excel exports
   ├── Facades/            # Custom facades
   ├── Helpers/            # Helper functions
   ├── Imports/            # Custom - Excel imports
   ├── Lists/              # Custom - Could be consolidated
   ├── Livewire/           # Livewire components
   ├── Mixins/             # Similar to Concerns
   ├── Observers/          # Eloquent observers
   ├── QueryFilters/       # Custom query filters
   ├── Rules/              # Validation rules
   ├── Scopes/             # Query scopes
   ├── Services/           # Service classes
   ├── Support/            # Support utilities
   ├── ValueObjects/       # Value objects
   └── View/               # View composers

⚠️ routes/
   ├── app.php             # Custom route file
   ├── asset.php           # Custom route file
   ├── auth.php            # Auth routes (Good)
   ├── chat.php            # Custom route file
   ├── command.php         # Custom route file
   ├── custom.php          # Custom route file
   ├── export.php          # Custom route file
   ├── gateway.php         # Custom route file
   ├── guest.php           # Custom route file
   ├── integration.php     # Custom route file
   ├── module.php          # Custom route file
   ├── report.php          # Custom route file
   └── site.php            # Custom route file

⚠️ Root Level Files (Should be moved)
   ├── billdesk/           # Payment gateway - should be in app/Services
   ├── fix_admin_school.php # Temporary - should be removed
   ├── setup_complete.php  # Setup script - should be Artisan command
   └── documentation/      # Good to have
```

---

<a name="recommended-changes"></a>
## Recommended Changes

### 1. Consolidate Route Files (Laravel 11 Best Practice)

**Current:** 15+ route files  
**Recommended:** Use route groups in main files

```php
// routes/web.php - Consolidate all web routes here
Route::middleware(['web'])->group(function () {
    
    // Guest routes
    require __DIR__.'/guest.php';
    
    // Authenticated routes
    Route::middleware(['auth'])->group(function () {
        require __DIR__.'/app.php';
        require __DIR__.'/module.php';
        
        // Feature-based grouping
        Route::prefix('chat')->group(base_path('routes/features/chat.php'));
        Route::prefix('export')->group(base_path('routes/features/export.php'));
        Route::prefix('reports')->group(base_path('routes/features/report.php'));
    });
});
```

**New Structure:**
```
routes/
├── web.php                 # Main web routes
├── api.php                 # API routes
├── console.php             # Console routes
├── channels.php            # Broadcast channels
├── auth.php                # Authentication routes
└── features/               # Feature-specific routes
    ├── chat.php
    ├── export.php
    ├── gateway.php
    ├── integration.php
    └── reports.php
```

### 2. Organize Services & Actions (Domain-Driven Design)

**Move from flat structure to domain-based:**

```
app/
├── Domain/                         # NEW: Domain logic
│   ├── Academic/
│   │   ├── Actions/
│   │   │   ├── CreateCourseAction.php
│   │   │   └── EnrollStudentAction.php
│   │   ├── Models/
│   │   │   ├── Course.php
│   │   │   └── Enrollment.php
│   │   ├── Services/
│   │   │   └── AcademicService.php
│   │   └── QueryFilters/
│   │       └── CourseFilter.php
│   │
│   ├── Finance/
│   │   ├── Actions/
│   │   ├── Models/
│   │   ├── Services/
│   │   │   ├── FeeService.php
│   │   │   └── PaymentGatewayService.php
│   │   └── Gateways/              # Move billdesk here
│   │       ├── BillDesk/
│   │       ├── Razorpay/
│   │       └── Stripe/
│   │
│   ├── Student/
│   ├── Employee/
│   ├── Communication/
│   └── Library/
│
└── Application/                    # Application layer
    ├── Http/
    ├── Console/
    └── Livewire/
```

### 3. Centralize Helper Functions

**Current:** `app/Helpers/` with multiple files  
**Recommended:** Use Laravel 11 service provider pattern

```php
// app/Support/helpers.php
if (! function_exists('get_team_setting')) {
    function get_team_setting(string $key, mixed $default = null): mixed
    {
        return app(\App\Services\SettingService::class)->get($key, $default);
    }
}

// composer.json - autoload helpers
"autoload": {
    "files": [
        "app/Support/helpers.php"
    ]
}
```

### 4. Organize Livewire Components

**Current:** Flat `app/Livewire/` structure  
**Recommended:** Feature-based organization

```
app/
└── Livewire/
    ├── Academic/
    │   ├── CourseList.php
    │   ├── CourseForm.php
    │   └── EnrollmentWizard.php
    ├── Finance/
    │   ├── FeeCollection.php
    │   └── PaymentReceipt.php
    ├── Student/
    │   ├── StudentProfile.php
    │   └── StudentList.php
    └── Shared/              # Reusable components
        ├── DataTable.php
        ├── Modal.php
        └── FileUpload.php
```

### 5. API Resource Structure (Laravel 11)

```
app/
└── Http/
    ├── Controllers/
    │   └── Api/                    # API Controllers
    │       ├── V1/                 # API versioning
    │       │   ├── StudentController.php
    │       │   └── CourseController.php
    │       └── V2/
    ├── Resources/
    │   └── Api/
    │       └── V1/
    │           ├── StudentResource.php
    │           ├── CourseResource.php
    │           └── Collections/
    │               └── StudentCollection.php
    └── Requests/
        └── Api/
            └── V1/
                ├── StoreStudentRequest.php
                └── UpdateStudentRequest.php
```

### 6. Testing Structure (Laravel 11 Pest)

```
tests/
├── Feature/
│   ├── Academic/
│   │   ├── CourseManagementTest.php
│   │   └── EnrollmentTest.php
│   ├── Finance/
│   │   └── FeeCollectionTest.php
│   └── Auth/
│       └── LoginTest.php
├── Unit/
│   ├── Services/
│   │   └── AcademicServiceTest.php
│   └── Models/
│       └── StudentTest.php
└── Pest.php
```

---

<a name="implementation-plan"></a>
## Implementation Plan

### Phase 1: Clean Up Root Directory (Week 1)

```bash
# Remove temporary files
rm fix_admin_school.php
rm fix_admin_school.sql

# Convert setup script to Artisan command
php artisan make:command SetupSchool
# Move logic from setup_complete.php to the command
rm setup_complete.php

# Move billdesk to proper location
mkdir -p app/Domain/Finance/Gateways
mv billdesk app/Domain/Finance/Gateways/BillDesk
```

### Phase 2: Consolidate Routes (Week 1-2)

```bash
# Create features directory
mkdir -p routes/features

# Move and reorganize routes
mv routes/chat.php routes/features/
mv routes/export.php routes/features/
mv routes/gateway.php routes/features/
mv routes/integration.php routes/features/
mv routes/report.php routes/features/

# Update web.php to include feature routes
```

### Phase 3: Reorganize Services (Week 2-3)

```bash
# Create domain structure
mkdir -p app/Domain/{Academic,Finance,Student,Employee,Communication}

# Move models to domains
# Move services to domains
# Update namespaces
```

### Phase 4: Livewire Organization (Week 3)

```bash
# Reorganize Livewire components
php artisan livewire:move OldComponent Academic/NewComponent
```

### Phase 5: Configuration Optimization (Week 4)

```bash
# Review and optimize config files
# Remove unused configurations
# Add proper environment-based configs
```

---

<a name="best-practices"></a>
## Laravel 11 Best Practices

### 1. Use Eloquent Relationships

```php
// app/Models/Student.php
class Student extends Model
{
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
    
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }
    
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'enrollments')
            ->withPivot('enrolled_at', 'status')
            ->withTimestamps();
    }
}
```

### 2. Use Form Requests for Validation

```php
// app/Http/Requests/StoreStudentRequest.php
class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Student::class);
    }
    
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:students'],
            'dob' => ['required', 'date', 'before:today'],
        ];
    }
}
```

### 3. Use Action Classes for Business Logic

```php
// app/Domain/Student/Actions/EnrollStudentAction.php
namespace App\Domain\Student\Actions;

class EnrollStudentAction
{
    public function execute(Student $student, Course $course, array $data): Enrollment
    {
        DB::beginTransaction();
        
        try {
            $enrollment = $student->enrollments()->create([
                'course_id' => $course->id,
                'enrolled_at' => now(),
                'status' => 'active',
                ...$data,
            ]);
            
            event(new StudentEnrolled($student, $course));
            
            DB::commit();
            
            return $enrollment;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
```

### 4. Use Events & Listeners

```php
// app/Events/StudentEnrolled.php
class StudentEnrolled
{
    public function __construct(
        public Student $student,
        public Course $course
    ) {}
}

// app/Listeners/SendEnrollmentNotification.php
class SendEnrollmentNotification
{
    public function handle(StudentEnrolled $event): void
    {
        $event->student->notify(new EnrollmentConfirmation($event->course));
    }
}

// app/Providers/EventServiceProvider.php
protected $listen = [
    StudentEnrolled::class => [
        SendEnrollmentNotification::class,
        UpdateStudentMetrics::class,
    ],
];
```

### 5. Use Resource Controllers

```php
// routes/web.php
Route::resource('students', StudentController::class);

// app/Http/Controllers/StudentController.php
class StudentController extends Controller
{
    public function index() {}      // GET /students
    public function create() {}     // GET /students/create
    public function store() {}      // POST /students
    public function show() {}       // GET /students/{id}
    public function edit() {}       // GET /students/{id}/edit
    public function update() {}     // PUT/PATCH /students/{id}
    public function destroy() {}    // DELETE /students/{id}
}
```

### 6. Use Query Scopes

```php
// app/Models/Student.php
class Student extends Model
{
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    public function scopeInCourse($query, Course $course)
    {
        return $query->whereHas('enrollments', function ($q) use ($course) {
            $q->where('course_id', $course->id);
        });
    }
}

// Usage
$activeStudents = Student::active()->get();
$courseStudents = Student::inCourse($course)->get();
```

### 7. Use Enums (PHP 8.1+)

```php
// app/Enums/StudentStatus.php
namespace App\Enums;

enum StudentStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case GRADUATED = 'graduated';
    case SUSPENDED = 'suspended';
    
    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::GRADUATED => 'Graduated',
            self::SUSPENDED => 'Suspended',
        };
    }
}

// Usage in model
class Student extends Model
{
    protected $casts = [
        'status' => StudentStatus::class,
    ];
}
```

### 8. Use Service Containers

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->app->singleton(PaymentGatewayService::class, function ($app) {
        return new PaymentGatewayService(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        );
    });
}

// Usage in controller
public function __construct(
    private PaymentGatewayService $paymentGateway
) {}
```

### 9. Use Database Transactions

```php
use Illuminate\Support\Facades\DB;

DB::transaction(function () use ($student, $fee) {
    $payment = Payment::create([...]);
    $fee->markAsPaid();
    $student->updateBalance();
});
```

### 10. Use Policies for Authorization

```php
// app/Policies/StudentPolicy.php
class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-students');
    }
    
    public function create(User $user): bool
    {
        return $user->can('create-students');
    }
    
    public function update(User $user, Student $student): bool
    {
        return $user->can('edit-students') 
            && $user->current_team_id === $student->team_id;
    }
}

// Usage in controller
public function update(Request $request, Student $student)
{
    $this->authorize('update', $student);
    // Update logic
}
```

---

<a name="migration-steps"></a>
## Step-by-Step Migration Guide

### Step 1: Backup Everything

```bash
# Backup database
php artisan backup:run

# Commit current state
git add .
git commit -m "Pre-restructuring backup"
git tag v1.0-before-restructure
```

### Step 2: Create New Directory Structure

```bash
# Create domain directories
mkdir -p app/Domain/{Academic,Finance,Student,Employee,Communication,Library,Transport,Hostel}

# Create feature routes
mkdir -p routes/features

# Create support directory
mkdir -p app/Support
```

### Step 3: Move Files Systematically

```bash
# Example: Move Academic domain
mkdir -p app/Domain/Academic/{Models,Services,Actions,Policies}
mv app/Models/Academic/* app/Domain/Academic/Models/

# Update namespaces in moved files
# Use IDE refactoring or search-replace
```

### Step 4: Update Composer Autoload

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "App\\Domain\\": "app/Domain/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        },
        "files": [
            "app/Support/helpers.php"
        ]
    }
}
```

```bash
composer dump-autoload
```

### Step 5: Run Tests

```bash
php artisan test
```

### Step 6: Clear All Caches

```bash
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan permission:cache-reset
```

---

## Performance Optimization

### 1. Use Laravel Octane (Optional)

```bash
composer require laravel/octane
php artisan octane:install --server=frankenphp
php artisan octane:start
```

### 2. Enable Caching

```bash
# Production caching
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 3. Use Queue Workers

```bash
# Run queue worker with supervisor
php artisan queue:work --queue=high,default,low --tries=3
```

### 4. Optimize Database Queries

```php
// Use eager loading to prevent N+1
$students = Student::with(['guardian', 'enrollments.course'])->get();

// Use chunking for large datasets
Student::chunk(100, function ($students) {
    foreach ($students as $student) {
        // Process student
    }
});
```

---

## Deployment Checklist

- [ ] Environment variables configured
- [ ] Database migrations run
- [ ] Seeders executed
- [ ] Storage linked: `php artisan storage:link`
- [ ] Caches cleared and rebuilt
- [ ] Queue workers configured
- [ ] Scheduled tasks configured (cron)
- [ ] SSL certificate installed
- [ ] Backups configured
- [ ] Monitoring setup (Horizon, logs)

---

## Useful Artisan Commands

```bash
# Generate files
php artisan make:model Student -mfsc
php artisan make:controller StudentController --resource
php artisan make:request StoreStudentRequest
php artisan make:policy StudentPolicy --model=Student
php artisan make:livewire Student/StudentList

# Database
php artisan migrate:status
php artisan db:seed --class=DatabaseSeeder

# Queue & Jobs
php artisan queue:work
php artisan queue:failed
php artisan queue:retry all

# Maintenance
php artisan down --secret="restructuring"
php artisan up

# Custom commands
php artisan app:setup-school
php artisan app:sync-permissions
```

---

**Last Updated:** 11 January 2026  
**Maintained By:** FW Technologies  
**Laravel Version:** 11.x
