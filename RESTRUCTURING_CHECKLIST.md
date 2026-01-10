# Laravel Framework Restructuring Checklist

## Pre-Restructuring

- [ ] Backup database: `php artisan backup:run`
- [ ] Commit all changes: `git add . && git commit -m "Pre-restructuring"`
- [ ] Create git tag: `git tag v1.0-before-restructure`
- [ ] Run tests: `php artisan test`
- [ ] Document current issues/bugs
- [ ] Review `LARAVEL_RESTRUCTURING_GUIDE.md`

---

## Phase 1: Clean Up Root Directory

- [ ] Run: `php restructure.php --dry-run` to preview
- [ ] Run: `php restructure.php --phase=1`
- [ ] Verify temporary files removed
- [ ] Check `routes/features/` directory created
- [ ] Commit changes: `git commit -m "Phase 1: Clean up root directory"`

---

## Phase 2: Reorganize Routes

- [ ] Run: `php restructure.php --phase=2`
- [ ] Review `routes/web.php.new`
- [ ] Compare with current `routes/web.php`
- [ ] Backup current: `mv routes/web.php routes/web.php.backup`
- [ ] Apply new: `mv routes/web.php.new routes/web.php`
- [ ] Test routes: `php artisan route:list`
- [ ] Test application in browser
- [ ] Commit changes: `git commit -m "Phase 2: Reorganize routes"`

---

## Phase 3: Create Domain Structure

- [ ] Run: `php restructure.php --phase=3`
- [ ] Verify domain directories created:
  - [ ] `app/Domain/Academic/`
  - [ ] `app/Domain/Finance/`
  - [ ] `app/Domain/Student/`
  - [ ] `app/Domain/Employee/`
  - [ ] `app/Domain/Communication/`
  - [ ] `app/Domain/Library/`
  - [ ] `app/Domain/Transport/`
  - [ ] `app/Domain/Hostel/`
  - [ ] `app/Domain/Exam/`
  - [ ] `app/Domain/Inventory/`
- [ ] Verify BillDesk moved to `app/Domain/Finance/Gateways/BillDesk/`
- [ ] Commit changes: `git commit -m "Phase 3: Create domain structure"`

---

## Phase 4: Organize Livewire Components

- [ ] Run: `php restructure.php --phase=4`
- [ ] Review Livewire component organization
- [ ] Move components manually (if needed):
  ```bash
  php artisan livewire:move OldComponent Academic/NewComponent
  ```
- [ ] Update component references in views
- [ ] Test Livewire functionality
- [ ] Commit changes: `git commit -m "Phase 4: Organize Livewire"`

---

## Phase 5: Update Configuration

- [ ] Run: `php restructure.php --phase=5`
- [ ] Review `composer.json.new`
- [ ] Backup current: `mv composer.json composer.json.backup`
- [ ] Apply new: `mv composer.json.new composer.json`
- [ ] Run: `composer dump-autoload`
- [ ] Verify `app/Support/helpers.php` created
- [ ] Test helper functions
- [ ] Commit changes: `git commit -m "Phase 5: Update configuration"`

---

## Manual Migrations

### Move Models to Domains

Academic Domain:
- [ ] Move `app/Models/Academic/*` → `app/Domain/Academic/Models/`
- [ ] Update namespace: `App\Domain\Academic\Models`
- [ ] Update imports in controllers/services
- [ ] Test Academic functionality

Finance Domain:
- [ ] Move `app/Models/Finance/*` → `app/Domain/Finance/Models/`
- [ ] Update namespace: `App\Domain\Finance\Models`
- [ ] Update imports
- [ ] Test Finance functionality

Student Domain:
- [ ] Move `app/Models/Student/*` → `app/Domain/Student/Models/`
- [ ] Update namespace: `App\Domain\Student\Models`
- [ ] Update imports
- [ ] Test Student functionality

Employee Domain:
- [ ] Move `app/Models/Employee/*` → `app/Domain/Employee/Models/`
- [ ] Update namespace: `App\Domain\Employee\Models`
- [ ] Update imports
- [ ] Test Employee functionality

### Create Service Classes

- [ ] Create `app/Domain/Academic/Services/AcademicService.php`
- [ ] Create `app/Domain/Finance/Services/FeeService.php`
- [ ] Create `app/Domain/Finance/Services/PaymentGatewayService.php`
- [ ] Create `app/Domain/Student/Services/StudentService.php`
- [ ] Create `app/Domain/Employee/Services/EmployeeService.php`

### Create Action Classes

- [ ] Create `app/Domain/Student/Actions/EnrollStudentAction.php`
- [ ] Create `app/Domain/Finance/Actions/ProcessPaymentAction.php`
- [ ] Create `app/Domain/Employee/Actions/AssignRoleAction.php`
- [ ] Create `app/Domain/Academic/Actions/CreateCourseAction.php`

### Update Controllers

- [ ] Refactor controllers to use Actions
- [ ] Inject Services via constructor
- [ ] Remove business logic from controllers
- [ ] Add proper Form Requests

---

## Testing & Verification

- [ ] Run all tests: `php artisan test`
- [ ] Test login/authentication
- [ ] Test student management
- [ ] Test fee collection
- [ ] Test employee management
- [ ] Test academic features
- [ ] Test reports/exports
- [ ] Test payment gateways
- [ ] Test Livewire components
- [ ] Test API endpoints (if applicable)

---

## Performance & Optimization

- [ ] Clear all caches:
  ```bash
  php artisan optimize:clear
  php artisan permission:cache-reset
  ```
- [ ] Cache for production:
  ```bash
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  php artisan event:cache
  ```
- [ ] Test page load times
- [ ] Check database queries (N+1 issues)
- [ ] Optimize eager loading

---

## Documentation Updates

- [ ] Update README.md with new structure
- [ ] Document new domains
- [ ] Update API documentation
- [ ] Create domain-specific docs
- [ ] Update deployment guide

---

## Code Quality

- [ ] Run PHP CS Fixer (if configured)
- [ ] Run PHPStan/Larastan (if configured)
- [ ] Check for unused imports
- [ ] Check for deprecated code
- [ ] Review and refactor long methods

---

## Final Steps

- [ ] Final testing round
- [ ] Update version number
- [ ] Create release tag: `git tag v2.0-restructured`
- [ ] Push to repository: `git push && git push --tags`
- [ ] Deploy to staging environment
- [ ] Deploy to production (if ready)

---

## Post-Restructuring

- [ ] Monitor error logs
- [ ] Check application performance
- [ ] Gather user feedback
- [ ] Document lessons learned
- [ ] Plan next improvements

---

## Notes & Issues

Record any issues or notes during restructuring:

```
Date: ___________
Issue: 
Solution:

---

Date: ___________
Issue: 
Solution:

---
```

---

## Quick Commands Reference

```bash
# Preview changes
php restructure.php --dry-run

# Run specific phase
php restructure.php --phase=1

# Run all phases
php restructure.php

# Update autoloader
composer dump-autoload

# Clear caches
php artisan optimize:clear
php artisan permission:cache-reset

# Test application
php artisan test

# Start server
php artisan serve

# Check routes
php artisan route:list

# Move Livewire component
php artisan livewire:move OldName Domain/NewName
```

---

**Started:** ___________  
**Completed:** ___________  
**Team Members:**
- [ ] Name: ___________
- [ ] Name: ___________

**Sign-off:**
- [ ] Developer
- [ ] QA
- [ ] Project Manager
