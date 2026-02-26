# SkulSoft Complete Documentation

**Project:** SkulSoft - School Management System  
**Version:** 2.0 (Laravel 11 DDD Architecture)  
**Developer:** FW Technologies  
**Last Updated:** 11 January 2026

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Installation Guide](#installation-guide)
3. [Authentication & Login](#authentication-login)
4. [Laravel Restructuring Guide](#laravel-restructuring)
5. [Domain Migration Guide](#domain-migration)
6. [Migration Checklist](#migration-checklist)
7. [API Reference](#api-reference)
8. [Troubleshooting](#troubleshooting)

---

<a name="project-overview"></a>
## 1. Project Overview

### About SkulSoft

SkulSoft is a comprehensive school management system built with Laravel 11 and modern web technologies. It handles all aspects of school administration including student management, fee collection, academic planning, examinations, and more.

### Technology Stack

- **Framework:** Laravel 11.45.0
- **PHP:** 8.2+ (Currently 8.4.10)
- **Frontend:** Livewire 3.6.3, Vue.js 3, Tailwind CSS
- **Database:** MySQL/MariaDB
- **Authentication:** Spatie Laravel Permission 6.18.0 (Teams enabled)
- **Key Packages:**
  - Laravel Horizon (Queue Management)
  - Maatwebsite Excel (Reports)
  - mPDF (PDF Generation)
  - Intervention Image (Image Processing)

### System Architecture

**Multi-Tenant:** Team-based with academic periods  
**Pattern:** Domain-Driven Design (DDD)  
**Structure:** Feature-based with domain isolation

### Key Features

- **Academic Management:** Courses, Batches, Subjects, Timetables
- **Student Management:** Enrollment, Attendance, Records
- **Fee Management:** Collection, Invoicing, Payment Gateways
- **Employee Management:** Staff, Teachers, Roles
- **Examination System:** Marks, Marksheets, Reports
- **Communication:** Chat, Notifications, Announcements
- **Library Management:** Books, Issue/Return
- **Transport Management:** Routes, Vehicles
- **Hostel Management:** Rooms, Allocations
- **Inventory Management:** Stock, Assets

---

<a name="installation-guide"></a>
## 2. Installation Guide

### Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL 5.7+ or MariaDB 10.3+
- Apache/Nginx
- Node.js & NPM (for asset compilation)

### Quick Installation (MAMP Environment)

#### Step 1: Environment Setup

```bash
# Navigate to project directory
cd /Applications/MAMP/htdocs/shulesoft/school-ms

# Copy environment file
cp .env.example .env
```

#### Step 2: Configure Database

Edit `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=8889
DB_DATABASE=SkulSoft
DB_USERNAME=root
DB_PASSWORD=root
```

#### Step 3: Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install NPM dependencies (optional)
npm install
npm run build
```

#### Step 4: Generate Application Key

```bash
php artisan key:generate
```

#### Step 5: Run Migrations

```bash
# Run all migrations
php artisan migrate --force

# Seed database with initial data
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=AssignPermissionSeeder
```

#### Step 6: Create Admin User

```bash
php artisan tinker
```

```php
$user = App\Models\User::create([
    'name' => 'System Administrator',
    'email' => 'admin@skulsoft.com',
    'username' => 'admin',
    'password' => bcrypt('admin123'),
    'status' => 'activated',
    'email_verified_at' => now(),
]);

$team = App\Models\Team::create(['name' => 'SkulSoft School']);

$period = App\Models\Academic\Period::create([
    'team_id' => $team->id,
    'code' => '2026-27',
    'name' => '2026-2027',
    'start_date' => '2026-01-01',
    'end_date' => '2026-12-31',
    'is_default' => true,
]);

$user->meta = [
    'current_team_id' => $team->id,
    'current_period_id' => $period->id,
];
$user->save();

$adminRole = Spatie\Permission\Models\Role::where('name', 'admin')->first();
$user->assignRole($adminRole);
```

#### Step 7: Link Storage

```bash
php artisan storage:link
```

#### Step 8: Clear Caches

```bash
php artisan optimize:clear
php artisan permission:cache-reset
```

#### Step 9: Start Server

```bash
php artisan serve
```

Visit: http://127.0.0.1:8000 (or 8001, 8002 if 8000 is occupied)

---

<a name="authentication-login"></a>
## 3. Authentication & Login

### Default Credentials

| Field | Value |
|-------|-------|
| **URL** | http://127.0.0.1:8002 |
| **Email** | admin@skulsoft.com |
| **Password** | admin123 |
| **Username** | admin |

> **⚠️ Security Warning:** Change the default password immediately after first login!

### User Management

#### Creating New Users

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@skulsoft.com',
    'username' => 'johndoe',
    'password' => Hash::make('secure-password'),
    'status' => 'activated',
    'email_verified_at' => now(),
    'meta' => [
        'current_team_id' => 1,
        'current_period_id' => 1,
    ],
]);

// Assign role
$user->assignRole('admin'); // or 'teacher', 'student', etc.
```

#### Resetting Passwords

```php
$user = User::where('email', 'admin@skulsoft.com')->first();
$user->password = Hash::make('new-secure-password');
$user->save();
```

#### User Status Values

- `activated` - User can login
- `deactivated` - User is blocked from login
- `pending` - User awaiting activation

### Role-Based Access Control (RBAC)

#### Available Roles

- **Admin** - Full system access
- **Teacher** - Academic and student management
- **Student** - Student portal access
- **Parent** - Parent portal access
- **Accountant** - Finance management
- **Librarian** - Library management
- **Receptionist** - Reception and inquiry

#### Checking Permissions

```php
// In controller
if ($user->can('manage-students')) {
    // User has permission
}

// In blade template
@can('manage-students')
    <!-- Content for users with permission -->
@endcan

// Check role
if ($user->hasRole('admin')) {
    // User is admin
}
```

---

<a name="laravel-restructuring"></a>
## 4. Laravel Restructuring Guide

### Overview

SkulSoft has been restructured from a flat Laravel application to a modern Domain-Driven Design (DDD) architecture following Laravel 11 best practices.

### New Project Structure

```
app/
├── Domain/                          # Business Domains
│   ├── Academic/
│   │   ├── Models/                  # Academic models
│   │   ├── Services/                # Business logic
│   │   ├── Actions/                 # Single-purpose actions
│   │   ├── Policies/                # Authorization policies
│   │   ├── QueryFilters/            # Database query filters
│   │   ├── Events/                  # Domain events
│   │   └── Listeners/               # Event listeners
│   ├── Finance/
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Actions/
│   │   └── Gateways/
│   │       └── BillDesk/            # Payment gateway
│   ├── Student/
│   ├── Employee/
│   ├── Communication/
│   ├── Library/
│   ├── Transport/
│   ├── Hostel/
│   ├── Exam/
│   └── Inventory/
│
├── Http/                            # Application Layer
│   ├── Controllers/
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
│
├── Livewire/                        # Feature-organized components
│   ├── Academic/
│   ├── Finance/
│   ├── Student/
│   ├── Employee/
│   ├── Communication/
│   └── Shared/
│
└── Support/                         # Helpers
    └── helpers.php

routes/
├── web.php                          # Main web routes
├── api.php                          # API routes
├── auth.php                         # Authentication routes
└── features/                        # Feature routes
    ├── chat.php
    ├── export.php
    ├── gateway.php
    ├── integration.php
    └── report.php
```

### Benefits of New Structure

1. **Domain Isolation** - Related code grouped together
2. **Scalability** - Easy to add new features
3. **Maintainability** - Clear separation of concerns
4. **Team Collaboration** - Multiple developers can work on different domains
5. **Testing** - Easier to write domain-specific tests
6. **Laravel Standards** - Follows official best practices

### Migration Status

#### ✅ Completed
- Infrastructure created (10 domains)
- Routes reorganized
- Academic domain migrated (21 models, 179 files updated)
- Autoloader configured
- Helper functions created

#### 🔄 In Progress
- Remaining domain migrations

#### ⏳ Pending
- Service class extraction
- Action class creation
- Comprehensive testing

---

<a name="domain-migration"></a>
## 5. Domain Migration Guide

### Migration Tool

**Script:** `migrate_domain.php`

**Usage:**
```bash
# Preview migration (dry-run)
php migrate_domain.php <Domain> --dry-run

# Execute migration
php migrate_domain.php <Domain>
```

**Available Domains:**
- Academic ✅ (Completed)
- Finance
- Student
- Employee
- Communication
- Library
- Transport
- Hostel
- Exam
- Inventory

### Recommended Migration Order

#### Week 1: Core Domains
1. **Academic** ✅ - Completed
2. **Student** - Next priority
3. **Finance** - After Student

#### Week 2: Supporting Domains
4. **Employee**
5. **Exam**
6. **Communication**

#### Week 3: Additional Features
7. **Library**
8. **Transport**
9. **Hostel**
10. **Inventory**

### Migration Process (Step-by-Step)

#### Example: Migrating Student Domain

**Step 1: Backup**
```bash
git add . && git commit -m "Pre-Student migration"
git tag migration-student-before
```

**Step 2: Preview**
```bash
php migrate_domain.php Student --dry-run
```

**Step 3: Execute**
```bash
php migrate_domain.php Student
```

**Step 4: Update Autoloader**
```bash
composer dump-autoload
```

**Step 5: Clear Caches**
```bash
php artisan optimize:clear
php artisan permission:cache-reset
```

**Step 6: Test**
```bash
# Run tests
php artisan test --filter=Student

# Manual testing
php artisan serve
```

**Step 7: Commit**
```bash
git add . && git commit -m "Migrate Student domain"
git tag migration-student-complete
```

### What the Migration Does

1. **Moves Models** - From `app/Models/{Domain}` to `app/Domain/{Domain}/Models`
2. **Updates Namespaces** - Changes to `App\Domain\{Domain}\Models`
3. **Updates Imports** - Scans and updates all references in:
   - Controllers
   - Services
   - Actions
   - Livewire components
   - Policies
   - Observers
4. **Cleans Up** - Removes empty old directories
5. **Verifies** - Confirms successful migration

---

<a name="migration-checklist"></a>
## 6. Migration Checklist

### Per-Domain Checklist

- [ ] Dry run completed
- [ ] Backup created (git commit + tag)
- [ ] Migration executed
- [ ] Composer autoload regenerated
- [ ] Caches cleared
- [ ] Tests passing
- [ ] Manual testing completed
- [ ] Changes committed to git

### Domain Progress

- [x] **Academic Domain** - 21 models, 179 files updated
- [ ] **Student Domain**
- [ ] **Finance Domain**
- [ ] **Employee Domain**
- [ ] **Exam Domain**
- [ ] **Communication Domain**
- [ ] **Library Domain**
- [ ] **Transport Domain**
- [ ] **Hostel Domain**
- [ ] **Inventory Domain**

### Post-Migration Tasks

- [ ] All domains migrated
- [ ] Service classes extracted
- [ ] Action classes created
- [ ] Comprehensive tests written
- [ ] API documentation updated
- [ ] README updated
- [ ] Production deployment prepared

---

<a name="api-reference"></a>
## 7. API Reference

### Authentication

All API requests require authentication via Laravel Sanctum.

**Headers:**
```
Accept: application/json
Authorization: Bearer {token}
```

### Base URL

```
http://127.0.0.1:8002/api/v1
```

### Common Endpoints

#### Authentication
- `POST /auth/login` - User login
- `POST /auth/logout` - User logout
- `POST /auth/register` - User registration

#### Academic
- `GET /app/academic/courses` - List courses
- `POST /app/academic/courses` - Create course
- `GET /app/academic/courses/{id}` - Get course
- `PUT /app/academic/courses/{id}` - Update course
- `DELETE /app/academic/courses/{id}` - Delete course

#### Students
- `GET /app/students` - List students
- `POST /app/students` - Create student
- `GET /app/students/{id}` - Get student
- `PUT /app/students/{id}` - Update student
- `DELETE /app/students/{id}` - Delete student

#### Finance
- `GET /app/finance/fees` - List fees
- `POST /app/finance/fees/pay` - Process payment
- `GET /app/finance/transactions` - List transactions

### Response Format

**Success:**
```json
{
    "success": true,
    "data": {},
    "message": "Operation successful"
}
```

**Error:**
```json
{
    "success": false,
    "message": "Error message",
    "errors": {}
}
```

---

<a name="troubleshooting"></a>
## 8. Troubleshooting

### Common Issues

#### 1. Class Not Found Error

**Problem:** `Class 'App\Models\Academic\Course' not found`

**Solution:**
```bash
composer dump-autoload
php artisan optimize:clear
```

#### 2. Routes Not Loading

**Problem:** Routes returning 404 errors

**Solution:**
```bash
php artisan route:clear
php artisan config:clear
php artisan route:list  # Verify routes
```

#### 3. Permission Errors

**Problem:** User cannot login or access features / "You are not allowed to login"

**Causes:**
- User status is not `activated`
- Email not verified
- No team assigned in meta
- No period assigned in meta
- No role assigned
- Role has NULL team_id (multi-tenant issue)
- Old namespace references after domain migration

**Solution:**
```bash
# Quick fix script
php fix_login.php

# Or manual check
php artisan tinker
>>> $user = App\Models\User::find(1);
>>> $user->status;  # Should be 'activated'
>>> $user->getRoleNames();  # Should show roles

# Clear permission cache
php artisan permission:cache-reset

# If you get "Class App\Models\Academic\Period not found" after migration:
./update_academic_namespace.sh

# Or manually update autoloader and clear caches
composer dump-autoload --optimize
php artisan optimize:clear
```

#### 4. Database Connection Failed

**Problem:** SQLSTATE[HY000] [2002] Connection refused

**Solution:**
- Check MAMP is running
- Verify database credentials in `.env`
- Ensure database exists
```bash
mysql -u root -p -h 127.0.0.1 -P 8889
SHOW DATABASES;
```

#### 5. Migration Errors

**Problem:** Migration fails or conflicts

**Solution:**
```bash
# Rollback to previous state
git checkout migration-{domain}-before

# Or reset specific migration
php artisan migrate:rollback --step=1
```

#### 6. Autoload Warnings

**Problem:** PSR-4 autoloading warnings

**Solution:**
These warnings for BillDesk are normal (uses different namespace). To silence:
```bash
composer dump-autoload --optimize
```

### Server Issues

#### Port Already in Use

```bash
# Use different port
php artisan serve --port=8080
```

#### Server Won't Start

```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Fix permissions
chmod -R 775 storage bootstrap/cache
```

### Cache Issues

#### Clear All Caches

```bash
php artisan optimize:clear  # Clears all caches
```

#### Individual Cache Clear

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
```

### Development Commands

#### Useful Artisan Commands

```bash
# List all routes
php artisan route:list

# List all routes for specific domain
php artisan route:list | grep academic

# Check application status
php artisan about

# Run tests
php artisan test

# Run specific test
php artisan test --filter=AcademicTest

# Generate IDE helper (if installed)
php artisan ide-helper:generate
```

#### Database Commands

```bash
# Fresh migration
php artisan migrate:fresh

# Fresh migration with seed
php artisan migrate:fresh --seed

# Check migration status
php artisan migrate:status

# Rollback last migration
php artisan migrate:rollback
```

#### Tinker Queries

```bash
php artisan tinker
```

```php
# Count users
App\Models\User::count();

# Find user
App\Models\User::find(1);

# Check roles
$user = App\Models\User::find(1);
$user->getRoleNames();

# Check permissions
$user->getAllPermissions()->pluck('name');

# List Academic models
App\Domain\Academic\Models\Course::all();
```

---

## Appendix

### Helper Functions

The following global helper functions are available throughout the application:

```php
// Get team setting
$value = get_team_setting('key', 'default');

// Get current team
$team = current_team();

// Get current academic period
$period = current_period();

// Format currency
$formatted = format_currency(1000.50); // "1,000.50 USD"

// Get academic year
$year = academic_year(); // "2026-27"
```

### Environment Variables

Key environment variables:

```env
APP_NAME=SkulSoft
APP_ENV=local|production
APP_DEBUG=true|false
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=8889
DB_DATABASE=SkulSoft
DB_USERNAME=root
DB_PASSWORD=root

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525

QUEUE_CONNECTION=sync|redis|database
```

### File Permissions

Required permissions:

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache  # Linux
```

### Git Workflow

Recommended git workflow during migration:

```bash
# Before each domain migration
git add .
git commit -m "Descriptive message"
git tag migration-{domain}-before

# After successful migration
git add .
git commit -m "Migrate {domain} domain"
git tag migration-{domain}-complete

# If rollback needed
git checkout migration-{domain}-before
```

---

## Support & Resources

### Documentation
- Laravel: https://laravel.com/docs/11.x
- Livewire: https://livewire.laravel.com
- Spatie Permission: https://spatie.be/docs/laravel-permission

### Contact
- **Website:** https://fwtechnologies.com
- **Email:** info@fwtechnologies.com
- **Support:** support@fwtechnologies.com

---

**Document Version:** 2.0  
**Last Updated:** 12 January 2026  
**Status:** Active Development  
**License:** Proprietary

---

## BRANDING UPDATE (January 12, 2026)

✅ **All branding has been updated from ScriptMint to SkulSoft by FW Technologies**

- Application Name: SkulSoft
- Developer: FW Technologies  
- Footer Credit: "Developed by FW Technologies"
- Meta Description: "School Management System by FW Technologies"

After logging in, do a hard refresh (Ctrl+Shift+R / Cmd+Shift+R) to see the new branding.
