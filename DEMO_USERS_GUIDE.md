# Demo Users Guide

## Overview

This guide provides demo user accounts for testing all 16 roles in the SkulSoft School Management System. All demo accounts use the same password for easy testing.

---

## Quick Start

### Method 1: Using Laravel Seeder (Recommended)

```bash
# Run the demo users seeder
php artisan db:seed --class=DemoUsersSeeder
```

### Method 2: Using SQL Dump

```bash
# Import the SQL dump
mysql -u your_username -p your_database < database/dumps/demo_users.sql

# Or using phpMyAdmin
# 1. Open phpMyAdmin
# 2. Select your database
# 3. Go to Import tab
# 4. Choose database/dumps/demo_users.sql
# 5. Click "Go"
```

---

## Demo User Accounts

All demo users share the same password: **`password123`**

| # | Role | Name | Username | Email | Password |
|---|------|------|----------|-------|----------|
| 1 | **Admin** | Admin User | `admin` | admin@demo.com | password123 |
| 2 | **Manager** | Manager Demo | `manager` | manager@demo.com | password123 |
| 3 | **Principal** | Principal Demo | `principal` | principal@demo.com | password123 |
| 4 | **Staff** | Staff Member | `staff` | staff@demo.com | password123 |
| 5 | **Accountant** | John Accountant | `accountant` | accountant@demo.com | password123 |
| 6 | **Librarian** | Sarah Librarian | `librarian` | librarian@demo.com | password123 |
| 7 | **Exam Incharge** | Mike Exam Coordinator | `exam-coordinator` | exam@demo.com | password123 |
| 8 | **Transport Incharge** | David Transport Manager | `transport` | transport@demo.com | password123 |
| 9 | **Inventory Incharge** | Lisa Inventory Manager | `inventory` | inventory@demo.com | password123 |
| 10 | **Mess Incharge** | Chef Mess Manager | `mess-manager` | mess@demo.com | password123 |
| 11 | **Hostel Incharge** | Robert Hostel Warden | `hostel-warden` | hostel@demo.com | password123 |
| 12 | **Attendance Assistant** | Mary Attendance Officer | `attendance` | attendance@demo.com | password123 |
| 13 | **Receptionist** | Emma Receptionist | `receptionist` | reception@demo.com | password123 |
| 14 | **Student** | Tom Student | `student` | student@demo.com | password123 |
| 15 | **Guardian** | Parent Guardian | `parent` | parent@demo.com | password123 |
| 16 | **User** | Basic User | `basicuser` | user@demo.com | password123 |

---

## Testing Different Roles

### Administrative Roles

#### 1. Admin (`admin` / `admin@demo.com`)
- **Access Level**: Full system access
- **What to Test**:
  - System configuration
  - User management
  - Team management
  - All modules access
  - Global settings

#### 2. Manager (`manager` / `manager@demo.com`)
- **Access Level**: High-level management
- **What to Test**:
  - Reports generation
  - Configuration management
  - User management
  - Post management
  - Academic operations

#### 3. Principal (`principal` / `principal@demo.com`)
- **Access Level**: School administration
- **What to Test**:
  - Student management
  - Staff management
  - Academic operations
  - Post creation
  - Reports viewing

#### 4. Staff (`staff` / `staff@demo.com`)
- **Access Level**: Teaching staff
- **What to Test**:
  - Class management
  - Student records viewing
  - Attendance marking
  - Grade management
  - Own profile management

---

### Departmental Roles

#### 5. Accountant (`accountant` / `accountant@demo.com`)
- **What to Test**:
  - Fee management
  - Payment records
  - Financial reports
  - Expense tracking
  - Receipt generation

#### 6. Librarian (`librarian` / `librarian@demo.com`)
- **What to Test**:
  - Book management
  - Book issue/return
  - Library reports
  - Student library records

#### 7. Exam Incharge (`exam-coordinator` / `exam@demo.com`)
- **What to Test**:
  - Exam scheduling
  - Grade entry
  - Result processing
  - Report card generation
  - Exam attendance

#### 8. Transport Incharge (`transport` / `transport@demo.com`)
- **What to Test**:
  - Route management
  - Vehicle management
  - Student transport assignment
  - Transport reports

#### 9. Inventory Incharge (`inventory` / `inventory@demo.com`)
- **What to Test**:
  - Stock management
  - Asset tracking
  - Purchase orders
  - Inventory reports

#### 10. Mess Incharge (`mess-manager` / `mess@demo.com`)
- **What to Test**:
  - Menu planning
  - Meal management
  - Mess reports
  - Student meal allocation

#### 11. Hostel Incharge (`hostel-warden` / `hostel@demo.com`)
- **What to Test**:
  - Room allocation
  - Hostel student management
  - Hostel facilities
  - Hostel reports

#### 12. Attendance Assistant (`attendance` / `attendance@demo.com`)
- **What to Test**:
  - Mark student attendance
  - View attendance reports
  - Student attendance tracking
  - Attendance notifications

#### 13. Receptionist (`receptionist` / `reception@demo.com`)
- **What to Test**:
  - Visitor management
  - Call logs
  - Basic inquiries
  - Front desk operations

---

### User Roles

#### 14. Student (`student` / `student@demo.com`)
- **What to Test**:
  - View own records
  - Assignments
  - Grades viewing
  - Timetable
  - Fee payment status

#### 15. Guardian/Parent (`parent` / `parent@demo.com`)
- **What to Test**:
  - View child's records
  - Communication with teachers
  - Fee payment
  - Attendance tracking
  - Grade reports

#### 16. Basic User (`basicuser` / `user@demo.com`)
- **What to Test**:
  - Limited system access
  - Profile management
  - Basic features

---

## Testing Scenarios

### Scenario 1: Complete Admin Workflow
1. Login as **Admin** (`admin`)
2. Create new academic period
3. Add courses and batches
4. Create student records
5. Assign staff to classes

### Scenario 2: Fee Management
1. Login as **Accountant** (`accountant`)
2. Create fee structures
3. Assign fees to students
4. Process payments
5. Generate receipts

### Scenario 3: Attendance Tracking
1. Login as **Attendance Assistant** (`attendance`)
2. Select batch and date
3. Mark student attendance
4. Send notifications
5. View attendance reports

### Scenario 4: Library Operations
1. Login as **Librarian** (`librarian`)
2. Add new books
3. Issue books to students
4. Track returns
5. Generate library reports

### Scenario 5: Exam Management
1. Login as **Exam Incharge** (`exam-coordinator`)
2. Create exam schedule
3. Enter marks/grades
4. Process results
5. Generate report cards

### Scenario 6: Student Portal
1. Login as **Student** (`student`)
2. View timetable
3. Check assignments
4. View grades
5. Check fee status

### Scenario 7: Parent Portal
1. Login as **Guardian** (`parent`)
2. View child's attendance
3. Check academic progress
4. View fee status
5. Communicate with teachers

---

## Advanced Testing

### Multi-User Session Testing

Test simultaneous logins:

1. **Browser 1**: Login as Admin
2. **Browser 2** (Incognito): Login as Manager
3. **Browser 3** (Different Browser): Login as Student

Verify:
- Session isolation
- Role-based access control
- Permission enforcement

### Permission Testing

For each role, verify:
- Can access allowed modules
- Cannot access restricted modules
- Appropriate error messages for unauthorized access

---

## Verification Commands

### Check if demo users were created:

```bash
php artisan tinker

# List all demo users
>>> \App\Models\User::where('email', 'like', '%@demo.com%')->get(['name', 'email', 'username'])

# Count demo users
>>> \App\Models\User::where('email', 'like', '%@demo.com%')->count()
```

### Check role assignments:

```bash
php artisan tinker

# Check specific user's role
>>> $user = \App\Models\User::where('email', 'admin@demo.com')->first();
>>> $user->getRoleNames()

# List all demo users with roles
>>> \App\Models\User::where('email', 'like', '%@demo.com%')
    ->with('roles')
    ->get()
    ->map(fn($u) => [
        'name' => $u->name,
        'email' => $u->email,
        'role' => $u->roles->pluck('name')->first()
    ])
```

### Verify permissions:

```bash
php artisan tinker

# Check what a user can do
>>> $user = \App\Models\User::where('email', 'accountant@demo.com')->first();
>>> $user->getAllPermissions()->pluck('name')
```

---

## Cleanup Demo Users

### Remove all demo users:

```bash
php artisan tinker

# Delete demo users
>>> \App\Models\User::where('email', 'like', '%@demo.com%')->delete()
```

### SQL Cleanup:

```sql
-- Delete demo users
DELETE FROM users WHERE email LIKE '%@demo.com%';

-- This will cascade delete:
-- - Role assignments (model_has_roles)
-- - Team assignments (team_user)
-- - User meta data
```

---

## Security Notes

### Important Warnings

1. **Never use these accounts in production!**
   - These are demo accounts with known credentials
   - Only use in development/testing environments

2. **Change passwords immediately if used in production**
   ```bash
   php artisan tinker
   >>> $user = \App\Models\User::where('email', 'admin@demo.com')->first();
   >>> $user->password = bcrypt('new-secure-password');
   >>> $user->save();
   ```

3. **Disable demo accounts in production**
   ```bash
   php artisan tinker
   >>> \App\Models\User::where('email', 'like', '%@demo.com%')
       ->update(['status' => 'disabled']);
   ```

---

## Troubleshooting

### Demo users not showing up?

**Check 1: Seeder ran successfully**
```bash
php artisan db:seed --class=DemoUsersSeeder
```

**Check 2: Roles exist**
```bash
php artisan db:seed --class=RoleSeeder
```

**Check 3: Team exists**
```bash
php artisan tinker
>>> \App\Models\Team::count()
# Should be > 0
```

### Cannot login with demo accounts?

**Issue**: "These credentials do not match our records"

**Fix 1**: Check password
- Password: `password123`
- Case-sensitive

**Fix 2**: Verify user exists
```bash
php artisan tinker
>>> \App\Models\User::where('email', 'admin@demo.com')->exists()
```

**Fix 3**: Check user status
```bash
php artisan tinker
>>> $user = \App\Models\User::where('email', 'admin@demo.com')->first();
>>> $user->status
# Should be 'activated'
```

### Role not assigned?

```bash
php artisan tinker

# Check and assign role manually
>>> $user = \App\Models\User::where('email', 'admin@demo.com')->first();
>>> $role = \Spatie\Permission\Models\Role::where('name', 'admin')->first();
>>> $user->assignRole($role);
```

---

## Password Hash Information

**Default Password**: `password123`

**Password Hash**: `$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi`

This is a bcrypt hash used in Laravel testing/seeding.

To generate a new password hash:
```bash
php artisan tinker
>>> bcrypt('your-password-here')
```

---

## Files Included

1. **DemoUsersSeeder.php** - Laravel seeder class
   - Location: `database/seeders/DemoUsersSeeder.php`
   - Usage: `php artisan db:seed --class=DemoUsersSeeder`

2. **demo_users.sql** - SQL dump file
   - Location: `database/dumps/demo_users.sql`
   - Usage: Import via MySQL or phpMyAdmin

3. **DEMO_USERS_GUIDE.md** - This documentation
   - Location: `DEMO_USERS_GUIDE.md`
   - Complete guide for using demo accounts

---

## Summary

**Total Demo Accounts**: 16 (one for each role)

**Default Password**: password123 (all accounts)

**Quick Login**:
- Admin: `admin` / `password123`
- Manager: `manager` / `password123`
- Principal: `principal` / `password123`
- (See full table above for all accounts)

**Setup Command**:
```bash
php artisan db:seed --class=DemoUsersSeeder
```

**Cleanup Command**:
```bash
php artisan tinker
>>> \App\Models\User::where('email', 'like', '%@demo.com%')->delete()
```

---

**Created**: February 23, 2026  
**System**: SkulSoft School Management System  
**Purpose**: Testing and demonstration of all user roles  
**Security Level**: Development/Testing only - DO NOT use in production!
