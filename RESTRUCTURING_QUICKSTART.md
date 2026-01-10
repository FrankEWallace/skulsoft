# Quick Start: Laravel Framework Restructuring

## What This Does

This restructuring transforms your SkulSoft project to follow **Laravel 11 framework best practices** including:

✅ **Domain-Driven Design** - Organize code by business domains  
✅ **Clean Architecture** - Separate concerns properly  
✅ **Laravel Conventions** - Follow official Laravel standards  
✅ **Better Maintainability** - Easier to understand and extend  
✅ **Industry Standards** - Professional project structure  

---

## Before You Start

### 1. Backup Everything

```bash
# Backup database
php artisan backup:run

# Commit current state
git add .
git commit -m "Pre-restructuring backup"
git tag v1.0-before-restructure
```

### 2. Check System Status

```bash
# Ensure tests pass
php artisan test

# Check for uncommitted changes
git status
```

---

## Running the Restructuring

### Option 1: Dry Run (See Changes Without Applying)

```bash
php restructure.php --dry-run
```

This shows what will happen without making any changes.

### Option 2: Run Specific Phase

```bash
# Phase 1: Clean up root directory
php restructure.php --phase=1

# Phase 2: Reorganize routes
php restructure.php --phase=2

# Phase 3: Create domain structure
php restructure.php --phase=3

# Phase 4: Organize Livewire components
php restructure.php --phase=4

# Phase 5: Update configuration
php restructure.php --phase=5
```

### Option 3: Run All Phases

```bash
php restructure.php
```

---

## What Each Phase Does

### Phase 1: Clean Up Root Directory
- Removes temporary files (fix_admin_school.php, etc.)
- Creates `routes/features/` directory
- Prepares for better organization

### Phase 2: Reorganize Routes
- Moves feature routes to `routes/features/`
- Creates new web.php structure
- Consolidates route files

### Phase 3: Create Domain Structure
- Creates domain directories (Academic, Finance, Student, etc.)
- Each domain gets: Models, Services, Actions, Policies
- Moves billdesk to `app/Domain/Finance/Gateways/`

### Phase 4: Livewire Organization
- Creates feature-based Livewire directories
- Provides guidance for moving components

### Phase 5: Configuration Updates
- Updates composer.json with new namespaces
- Creates app/Support/helpers.php
- Configures autoloading

---

## After Restructuring

### 1. Update Autoloader

```bash
composer dump-autoload
```

### 2. Clear All Caches

```bash
php artisan optimize:clear
php artisan permission:cache-reset
```

### 3. Review Generated Files

The script creates `.new` files for safety:
- `routes/web.php.new` - Review and replace original
- `composer.json.new` - Review and replace original

```bash
# After reviewing, replace files:
mv routes/web.php routes/web.php.backup
mv routes/web.php.new routes/web.php

mv composer.json composer.json.backup
mv composer.json.new composer.json
```

### 4. Update Namespaces

You'll need to manually update namespaces for moved files:

```php
// Before
namespace App\Models\Academic;

// After (if moved to domain)
namespace App\Domain\Academic\Models;
```

**Use your IDE's refactoring tools** to update namespaces automatically.

### 5. Test Everything

```bash
# Run all tests
php artisan test

# Test in browser
php artisan serve
# Visit: http://127.0.0.1:8002
```

---

## New Project Structure

After restructuring, your project will look like this:

```
app/
├── Domain/                         # NEW: Business domains
│   ├── Academic/
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Actions/
│   │   ├── Policies/
│   │   └── QueryFilters/
│   ├── Finance/
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Actions/
│   │   └── Gateways/              # Payment gateways
│   │       └── BillDesk/
│   ├── Student/
│   ├── Employee/
│   └── Communication/
│
├── Http/                           # Application layer
│   ├── Controllers/
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
│
├── Livewire/                       # Organized by feature
│   ├── Academic/
│   ├── Finance/
│   ├── Student/
│   └── Shared/
│
└── Support/                        # NEW: Helper functions
    └── helpers.php

routes/
├── web.php                         # Main web routes
├── api.php                         # API routes
├── auth.php                        # Auth routes
└── features/                       # NEW: Feature routes
    ├── chat.php
    ├── export.php
    ├── gateway.php
    ├── integration.php
    └── report.php
```

---

## Best Practices After Restructuring

### 1. Use Domain Models

```php
// Before
use App\Models\Academic\Course;

// After
use App\Domain\Academic\Models\Course;
```

### 2. Use Action Classes

```php
// app/Domain/Student/Actions/EnrollStudentAction.php
namespace App\Domain\Student\Actions;

class EnrollStudentAction
{
    public function execute(Student $student, Course $course): Enrollment
    {
        // Business logic here
    }
}

// In controller
public function enroll(Student $student, Course $course)
{
    return app(EnrollStudentAction::class)->execute($student, $course);
}
```

### 3. Use Service Classes

```php
// app/Domain/Finance/Services/FeeService.php
namespace App\Domain\Finance\Services;

class FeeService
{
    public function calculateTotalFees(Student $student): float
    {
        // Complex fee calculation logic
    }
}
```

### 4. Use Feature Routes

```php
// routes/features/chat.php
Route::prefix('chat')->middleware(['auth'])->group(function () {
    Route::get('/', ChatController::class)->name('chat.index');
    Route::post('/send', [ChatController::class, 'send'])->name('chat.send');
});
```

---

## Troubleshooting

### Class Not Found Errors

```bash
# Regenerate autoload files
composer dump-autoload

# Clear compiled files
php artisan clear-compiled
```

### Route Not Found

```bash
# Clear route cache
php artisan route:clear

# List all routes
php artisan route:list
```

### View Not Found

```bash
# Clear view cache
php artisan view:clear
```

### Permission Issues

```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
```

---

## Rollback (If Needed)

If something goes wrong:

```bash
# Restore from git tag
git checkout v1.0-before-restructure

# Or restore from backup
git reset --hard HEAD~1
```

---

## Need Help?

1. **Read Full Guide:** `LARAVEL_RESTRUCTURING_GUIDE.md`
2. **Laravel Docs:** https://laravel.com/docs/11.x
3. **Support:** info@fwtechnologies.com

---

**Remember:** This is a gradual process. You don't have to restructure everything at once. Start with one domain and migrate gradually.

**Last Updated:** 11 January 2026  
**Version:** 1.0
