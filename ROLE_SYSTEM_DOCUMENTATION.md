# User Role System Documentation

## Overview

Your SkulSoft School Management System uses **Spatie Laravel Permission** package for role-based access control (RBAC). The system supports **unlimited custom roles** in addition to the predefined system roles.

## System Architecture

### Role Management Features
- **Unlimited Roles**: The system can support unlimited user-based roles
- **Team-Based Roles**: Roles can be scoped to specific teams/schools
- **Global Roles**: Admin role is global (works across all teams)
- **Custom Roles**: Users can create custom roles beyond the default ones
- **Protected Default Roles**: System default roles cannot be deleted
- **Granular Permissions**: Each role has specific permissions assigned

---

## Default System Roles (16 Total)

### 1. Admin
- **Type**: Global Role (team_id: null)
- **Access**: Full system access across all teams
- **Cannot be deleted**: Yes
- **Use Case**: System administrators

### 2. Manager
- **Type**: Team-specific
- **Permissions**: Highest level staff with management capabilities
- **Key Access**: 
  - Configuration management
  - Reports access
  - Post management
  - User management
  
### 3. Principal
- **Type**: Team-specific
- **Permissions**: School principal with administrative rights
- **Key Access**:
  - Student management
  - Staff management
  - Academic operations
  - Post creation

### 4. Staff
- **Type**: Team-specific
- **Permissions**: General teaching and administrative staff
- **Key Access**:
  - Class management
  - Student records
  - Attendance
  - Grade management

### 5. Accountant
- **Type**: Team-specific
- **Permissions**: Financial management
- **Key Access**:
  - Fee management
  - Payment records
  - Financial reports
  - Expense tracking

### 6. Librarian
- **Type**: Team-specific
- **Permissions**: Library management
- **Key Access**:
  - Book management
  - Book issue/return
  - Library reports

### 7. Exam Incharge
- **Type**: Team-specific
- **Permissions**: Examination management
- **Key Access**:
  - Exam scheduling
  - Grade entry
  - Result processing
  - Report card generation

### 8. Transport Incharge
- **Type**: Team-specific
- **Permissions**: Transport management
- **Key Access**:
  - Route management
  - Vehicle management
  - Student transport assignment

### 9. Inventory Incharge
- **Type**: Team-specific
- **Permissions**: Inventory and asset management
- **Key Access**:
  - Stock management
  - Asset tracking
  - Purchase orders

### 10. Mess Incharge
- **Type**: Team-specific
- **Permissions**: Mess/cafeteria management
- **Key Access**:
  - Menu planning
  - Meal management
  - Mess reports

### 11. Hostel Incharge
- **Type**: Team-specific
- **Permissions**: Hostel/dormitory management
- **Key Access**:
  - Room allocation
  - Hostel student management
  - Hostel facilities

### 12. Attendance Assistant
- **Type**: Team-specific
- **Permissions**: Attendance management
- **Key Access**:
  - Mark attendance
  - View attendance reports
  - Student attendance tracking

### 13. Receptionist
- **Type**: Team-specific
- **Permissions**: Front desk operations
- **Key Access**:
  - Visitor management
  - Call logs
  - Basic inquiries

### 14. Student
- **Type**: Team-specific
- **Permissions**: Student portal access
- **Key Access**:
  - View own records
  - Assignments
  - Grades
  - Timetable
  - Fee payments

### 15. Guardian
- **Type**: Team-specific
- **Permissions**: Parent/guardian portal access
- **Key Access**:
  - View child's records
  - Communication with teachers
  - Fee payment
  - Attendance tracking

### 16. User
- **Type**: Team-specific
- **Permissions**: Basic user access
- **Key Access**:
  - Limited system access
  - Profile management

---

## Role Capacity

### Unlimited Custom Roles

The system supports unlimited custom roles through:

1. **Dynamic Role Creation**
   ```php
   // Roles can be created via:
   // - Admin Panel: Team Settings → Roles
   // - API: POST /api/v1/app/teams/{team}/roles
   ```

2. **Team-Based Isolation**
   - Each team/school can have its own set of roles
   - Roles are scoped by `team_id`
   - No hard limit on number of roles

3. **Flexible Permissions**
   - Each custom role can have unique permission combinations
   - Permissions are granular and modular
   - Over 200+ different permissions available

---

## Technical Details

### Database Structure

**Table**: `roles`
```
- id (primary key)
- uuid (unique identifier)
- name (role name, slug format)
- guard_name (default: 'web')
- team_id (null for global roles, integer for team-specific)
- created_at
- updated_at
```

**Relationships**:
- `model_has_roles` - Links users to roles
- `role_has_permissions` - Links roles to permissions
- `roles.team_id` → `teams.id` - Team association

### Current Database Status

Based on your database:
```
Total Roles: 16
All roles are currently team-specific (Team ID: 1)
```

### Role Management

**Create Custom Role:**
1. Navigate to: Team Settings → Roles
2. Click "Add New Role"
3. Enter role name
4. Assign permissions
5. Save

**API Endpoint:**
```
POST /api/v1/app/teams/{team_id}/roles
Body: {
  "name": "Custom Role Name"
}
```

**Protected Roles:**
- Default system roles cannot be deleted
- Identified by `is_default` attribute
- Defined in `/resources/var/permission.json`

---

## Permission System

### Permission Categories

The system has permissions organized into categories:

1. **General** - Login, profile, posts
2. **Team** - Organization and team management
3. **Config** - System configuration
4. **Academic** - Classes, sections, batches
5. **Student** - Student management
6. **Employee** - Staff management
7. **Finance** - Fees, payments, transactions
8. **Library** - Book management
9. **Transport** - Vehicle and route management
10. **Exam** - Examination system
11. **Inventory** - Stock management
12. **Hostel** - Hostel management
13. **Mess** - Mess/cafeteria
14. **Attendance** - Student/staff attendance
15. **Communication** - Messages, notifications

### Permission Assignment

Permissions are assigned in:
- **File**: `/resources/var/permission.json`
- **Format**: 
  ```json
  {
    "permission:name": [
      "role1",
      "role2"
    ]
  }
  ```

---

## Best Practices

### Role Naming Conventions
- Use kebab-case: `exam-coordinator`
- Be descriptive: `class-teacher` not `ct`
- Use standard terms: `accountant` not `money-person`

### Permission Assignment
- Follow principle of least privilege
- Assign only necessary permissions
- Review regularly
- Test before deploying

### Custom Roles
- Create role hierarchy
- Document custom roles
- Avoid duplicating default roles
- Consider team structure

---

## How to Check Roles in Your System

### Via Tinker:
```bash
php artisan tinker

# Count all roles
>>> \Spatie\Permission\Models\Role::count()

# List all roles
>>> \Spatie\Permission\Models\Role::all(['name', 'team_id'])

# Roles for specific team
>>> \Spatie\Permission\Models\Role::where('team_id', 1)->get()

# Users with specific role
>>> \App\Models\User::role('principal')->get()

# Check user's roles
>>> $user = \App\Models\User::first()
>>> $user->getRoleNames()
```

### Via Database:
```sql
-- All roles
SELECT id, name, team_id FROM roles ORDER BY name;

-- Roles by team
SELECT id, name FROM roles WHERE team_id = 1;

-- User role assignments
SELECT u.name, r.name as role 
FROM users u 
JOIN model_has_roles mhr ON u.id = mhr.model_id 
JOIN roles r ON mhr.role_id = r.id;
```

---

## Migration & Seeding

### Role Seeder
**File**: `/database/seeders/RoleSeeder.php`
- Runs automatically during setup
- Creates default roles for each team
- Preserves custom roles (doesn't delete them)

### Permission Assignment Seeder
**File**: `/database/seeders/AssignPermissionSeeder.php`
- Assigns permissions to roles
- Based on `/resources/var/permission.json`
- Runs after role seeding

### Manual Seeding:
```bash
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=AssignPermissionSeeder
```

---

## Summary

### How many roles can the system support?

**Answer: UNLIMITED**

- **16 default system roles** (predefined)
- **Unlimited custom roles** (can be created as needed)
- **Team-based isolation** (each school/team can have own roles)
- **Scalable architecture** (no technical limitations)
- **Flexible permissions** (granular control over access)

### Role Capacity by Category:
- **Administrative Roles**: 6 (Admin, Manager, Principal, Staff, Receptionist, User)
- **Departmental Roles**: 7 (Accountant, Librarian, Exam, Transport, Inventory, Mess, Hostel Incharge)
- **User Roles**: 3 (Student, Guardian, Attendance Assistant)
- **Custom Roles**: Unlimited (created as needed)

**Total Theoretical Limit**: No hard limit - limited only by database capacity and practical management needs.

---

**Date Created**: February 23, 2026  
**System**: SkulSoft School Management System  
**Package**: Spatie Laravel Permission v6.0  
**Database**: Multi-tenant role system with team isolation
