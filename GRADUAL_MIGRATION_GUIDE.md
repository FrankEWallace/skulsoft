# Gradual Domain Migration Guide

## Overview

This guide helps you migrate SkulSoft from flat structure to domain-driven architecture **one domain at a time**.

---

## ⚙️ Migration Tool

**Script:** `migrate_domain.php`

**Usage:**
```bash
# Dry run (preview only)
php migrate_domain.php <Domain> --dry-run

# Actual migration
php migrate_domain.php <Domain>
```

**Available Domains:**
- Academic
- Finance
- Student
- Employee
- Communication
- Library
- Transport
- Hostel
- Exam
- Inventory

---

## 📋 Migration Order (Recommended)

### Priority 1: Core Domains (Week 1)
1. **Academic** - Course, Batch, Period, Subject models
2. **Student** - Student enrollment and management
3. **Finance** - Fee collection and payments

### Priority 2: Supporting Domains (Week 2)
4. **Employee** - Staff and teacher management
5. **Exam** - Examination system
6. **Communication** - Messaging and notifications

### Priority 3: Additional Features (Week 3)
7. **Library** - Library management
8. **Transport** - Transport management
9. **Hostel** - Hostel management
10. **Inventory** - Inventory management

---

## 🚀 Step-by-Step Migration Process

### Phase 1: Academic Domain (Start Here)

#### Step 1: Preview Migration
```bash
php migrate_domain.php Academic --dry-run
```

**Expected Output:**
- Lists 21 Academic model files
- Shows namespace changes
- Shows files that will be updated

#### Step 2: Backup
```bash
# Commit current state
git add .
git commit -m "Pre-Academic domain migration backup"
git tag migration-academic-before
```

#### Step 3: Run Migration
```bash
php migrate_domain.php Academic
```

#### Step 4: Update Autoloader
```bash
composer dump-autoload
```

#### Step 5: Clear Caches
```bash
php artisan optimize:clear
php artisan permission:cache-reset
```

#### Step 6: Test
```bash
# Run tests
php artisan test --filter=Academic

# Start server and test manually
php artisan serve
```

Visit: http://127.0.0.1:8002
- Test Academic features
- Check course management
- Verify batch operations
- Test subject assignment

#### Step 7: Commit
```bash
git add .
git commit -m "Migrate Academic domain to DDD structure"
git tag migration-academic-complete
```

---

### Phase 2: Student Domain

#### Step 1: Preview
```bash
php migrate_domain.php Student --dry-run
```

#### Step 2: Migrate
```bash
git add . && git commit -m "Pre-Student migration"
php migrate_domain.php Student
composer dump-autoload
php artisan optimize:clear
```

#### Step 3: Test
```bash
php artisan test --filter=Student
# Manual testing
```

#### Step 4: Commit
```bash
git add . && git commit -m "Migrate Student domain"
```

---

### Phase 3: Finance Domain

#### Step 1: Preview
```bash
php migrate_domain.php Finance --dry-run
```

#### Step 2: Migrate
```bash
git add . && git commit -m "Pre-Finance migration"
php migrate_domain.php Finance
composer dump-autoload
php artisan optimize:clear
```

#### Step 3: Test (Important - Payment Features)
```bash
# Test fee collection
# Test payment gateways
# Test invoicing
php artisan test --filter=Finance
```

#### Step 4: Commit
```bash
git add . && git commit -m "Migrate Finance domain"
```

---

### Repeat for Remaining Domains

Continue with: Employee, Exam, Communication, Library, Transport, Hostel, Inventory

---

## ✅ Verification Checklist

After each domain migration:

- [ ] Models moved to `app/Domain/{Domain}/Models/`
- [ ] Old directory removed
- [ ] Namespaces updated in model files
- [ ] Imports updated in controllers
- [ ] Imports updated in services
- [ ] Imports updated in Livewire components
- [ ] Composer autoload regenerated
- [ ] Caches cleared
- [ ] Tests passing
- [ ] Manual testing completed
- [ ] Changes committed to git

---

## 🔍 Troubleshooting

### Issue: Class Not Found

```bash
# Solution
composer dump-autoload
php artisan optimize:clear
```

### Issue: Namespace Errors

Check that imports are updated:
```php
// Old (Wrong)
use App\Models\Academic\Course;

// New (Correct)
use App\Domain\Academic\Models\Course;
```

### Issue: Routes Not Working

```bash
php artisan route:clear
php artisan config:clear
```

### Issue: Need to Rollback

```bash
# Rollback to previous commit
git reset --hard HEAD~1

# Or use specific tag
git checkout migration-academic-before
```

---

## 📊 Migration Progress Tracker

### Academic Domain
- [ ] Dry run completed
- [ ] Migration executed
- [ ] Tests passing
- [ ] Committed

### Student Domain
- [ ] Dry run completed
- [ ] Migration executed
- [ ] Tests passing
- [ ] Committed

### Finance Domain
- [ ] Dry run completed
- [ ] Migration executed
- [ ] Tests passing
- [ ] Committed

### Employee Domain
- [ ] Dry run completed
- [ ] Migration executed
- [ ] Tests passing
- [ ] Committed

### Exam Domain
- [ ] Dry run completed
- [ ] Migration executed
- [ ] Tests passing
- [ ] Committed

### Communication Domain
- [ ] Dry run completed
- [ ] Migration executed
- [ ] Tests passing
- [ ] Committed

### Library Domain
- [ ] Dry run completed
- [ ] Migration executed
- [ ] Tests passing
- [ ] Committed

### Transport Domain
- [ ] Dry run completed
- [ ] Migration executed
- [ ] Tests passing
- [ ] Committed

### Hostel Domain
- [ ] Dry run completed
- [ ] Migration executed
- [ ] Tests passing
- [ ] Committed

### Inventory Domain
- [ ] Dry run completed
- [ ] Migration executed
- [ ] Tests passing
- [ ] Committed

---

## 🎯 After All Migrations Complete

### 1. Final Verification
```bash
# Run full test suite
php artisan test

# Check for any remaining old namespaces
grep -r "use App\\Models\\Academic" app/
grep -r "use App\\Models\\Finance" app/
grep -r "use App\\Models\\Student" app/
```

### 2. Clean Up
```bash
# Remove empty old directories
find app/Models -type d -empty -delete
```

### 3. Update Documentation
- Update README.md with new structure
- Document domain responsibilities
- Update API documentation

### 4. Performance Optimization
```bash
# Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 5. Final Commit
```bash
git add .
git commit -m "Complete domain migration to DDD architecture"
git tag v2.0-ddd-complete
```

---

## 💡 Best Practices During Migration

### 1. One Domain at a Time
- Don't rush
- Test thoroughly after each domain
- Commit frequently

### 2. Maintain Backups
- Git tags for each phase
- Database backups before major changes

### 3. Test Everything
- Automated tests
- Manual testing of all features
- Check error logs

### 4. Update Gradually
- Controllers can reference new namespaces gradually
- No need to refactor everything at once

### 5. Document Issues
- Keep notes of any problems
- Document solutions for team

---

## 📞 Need Help?

### Check These First:
1. **Migration script output** - Review errors carefully
2. **Laravel logs** - `storage/logs/laravel.log`
3. **Composer autoload** - Run `composer dump-autoload`

### Common Commands:
```bash
# View recent errors
tail -f storage/logs/laravel.log

# List all routes
php artisan route:list | grep academic

# Check specific model
php artisan tinker
>>> App\Domain\Academic\Models\Course::count();
```

---

## 🎊 Ready to Start?

### Quick Start Commands:
```bash
# 1. Preview what will happen
php migrate_domain.php Academic --dry-run

# 2. Backup current state
git add . && git commit -m "Pre-migration backup"
git tag migration-start

# 3. Migrate Academic domain
php migrate_domain.php Academic

# 4. Update and test
composer dump-autoload
php artisan optimize:clear
php artisan serve

# 5. Test in browser
# Visit: http://127.0.0.1:8002

# 6. Commit if successful
git add . && git commit -m "Migrate Academic domain"
```

---

**Timeline Estimate:**
- Academic domain: 30-45 minutes
- Each additional domain: 20-30 minutes
- Total for all 10 domains: 4-6 hours

**Start with Academic domain and work through one domain per session!**

---

**Created:** 11 January 2026  
**Version:** 1.0  
**Status:** Ready to Execute
