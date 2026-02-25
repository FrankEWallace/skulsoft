# Demo Data Setup Guide

> **📘 Comprehensive Student Data**: For detailed student demo data, see **[STUDENT_DEMO_DATA.md](STUDENT_DEMO_DATA.md)**

## Overview
This guide explains the demo data that has been populated for testing the School Management System.

## What Was Created

### 1. ✅ Demo User Accounts (16 users)
All demo users have the password: `password123`

| Email | Role | Name | Has Full Data |
|-------|------|------|---------------|
| principal@demo.com | principal | Principal Demo | ✅ Yes (Employee) |
| manager@demo.com | manager | Manager Demo | ✅ Yes (Employee) |
| staff@demo.com | staff | Staff Member | ✅ Yes (Employee) |
| accountant@demo.com | accountant | John Accountant | ✅ Yes (Employee) |
| librarian@demo.com | librarian | Sarah Librarian | ✅ Yes (Employee) |
| exam@demo.com | exam-incharge | Mike Exam Coordinator | ✅ Yes (Employee) |
| transport@demo.com | transport-incharge | David Transport Manager | ✅ Yes (Employee) |
| inventory@demo.com | inventory-incharge | Lisa Inventory Manager | ⚠️ User only |
| mess@demo.com | mess-incharge | Chef Mess Manager | ⚠️ User only |
| hostel@demo.com | hostel-incharge | Robert Hostel Warden | ⚠️ User only |
| attendance@demo.com | attendance-assistant | Mary Attendance Officer | ⚠️ User only |
| reception@demo.com | receptionist | Emma Receptionist | ⚠️ User only |
| student@demo.com | student | Tom Student | ✅ Yes (Student) |
| parent@demo.com | guardian | Parent Guardian | ✅ Yes (Guardian) |
| user@demo.com | user | Basic User | ⚠️ User only |

### 2. ✅ Employee Data (7 employees)

All employee accounts have complete records including:
- **Contact information** (name, email, phone)
- **Employee record** (code number, joining date)
- **Designation assignment** (Principal, Teacher, etc.)
- **Department assignment** (Administration, Academics, etc.)

| Employee | Code | Designation | Department |
|----------|------|-------------|------------|
| Principal Demo | EMP001 | Principal | Administration |
| Manager Demo | EMP002 | Vice Principal | Administration |
| Staff Member | EMP003 | Teacher | Academics |
| John Accountant | EMP004 | Accountant | Accounts |
| Sarah Librarian | EMP005 | Librarian | Library |
| Mike Exam Coordinator | EMP006 | Exam Coordinator | Examination |
| David Transport Manager | EMP007 | Transport Manager | Transport |

### 3. ✅ Student Data (14 students with full records)

**See [STUDENT_DEMO_DATA.md](STUDENT_DEMO_DATA.md) for complete details**

#### Academic Structure Created:
- **1 Division**: Primary Section
- **6 Courses**: Grade 1, Grade 2, Grade 3, Grade 4, Grade 5, Grade 6
- **12 Batches**: 2 sections (A & B) per grade
- **14 Students**: Complete records across all grades

#### Sample Students:
| Student | Grade | Section | Admission # | Email |
|---------|-------|---------|-------------|-------|
| Emma Johnson | Grade 1 | A | ADM2026001 | emma.johnson@demo.com |
| Liam Smith | Grade 1 | A | ADM2026002 | liam.smith@demo.com |
| Olivia Williams | Grade 1 | B | ADM2026003 | olivia.williams@demo.com |
| Noah Brown | Grade 2 | A | ADM2025001 | noah.brown@demo.com |
| Ava Jones | Grade 2 | A | ADM2025002 | ava.jones@demo.com |
| Ethan Garcia | Grade 3 | A | ADM2024001 | ethan.garcia@demo.com |
| Sophia Martinez | Grade 3 | A | ADM2024002 | sophia.martinez@demo.com |
| Mason Rodriguez | Grade 4 | A | ADM2023001 | mason.rodriguez@demo.com |
| Isabella Davis | Grade 4 | A | ADM2023002 | isabella.davis@demo.com |
| Lucas Miller | Grade 5 | A | ADM2022001 | lucas.miller@demo.com |
| Mia Wilson | Grade 5 | A | ADM2022002 | mia.wilson@demo.com |
| Tom Student (Demo) | Grade 5 | B | ADM2022003 | student@demo.com 🔑 |
| Alexander Moore | Grade 6 | A | ADM2021001 | alexander.moore@demo.com |
| Charlotte Taylor | Grade 6 | A | ADM2021002 | charlotte.taylor@demo.com |

**🔑 Login Available**: student@demo.com / password123

#### Each Student Has:
- ✅ Complete contact information (name, email, phone)
- ✅ Full address (street, city, state, zipcode)
- ✅ Parent information (father's name, mother's name)
- ✅ Personal details (gender, birth date, blood group)
- ✅ Registration record (approved)
- ✅ Admission record with unique admission number
- ✅ Student record with batch and roll number assignment

#### Recreate Student Data:
```bash
php artisan db:seed --class=StudentDemoDataSeeder
```

### 4. ✅ Organizational Structure

**Departments** (6):
- Administration
- Academics
- Accounts
- Library
- Transport
- Examination

**Designations** (8):
- Principal
- Vice Principal
- Senior Teacher
- Teacher
- Accountant
- Librarian
- Transport Manager
- Exam Coordinator

### 5. ✅ Academic Period
- **Period**: 2026-2027
- **Start Date**: 2025-09-01
- **End Date**: 2026-06-30

## What's Missing

### ⚠️ Courses & Batches
The system needs courses (classes/grades) and batches (sections) to be created through the admin panel before students can be fully enrolled.

**To complete student setup:**
1. Login as `principal@demo.com` or `manager@demo.com`
2. Navigate to **Academic → Courses**
3. Create courses (e.g., Grade 1, Grade 2, etc.)
4. Navigate to **Academic → Batches**
5. Create batches/sections for each course (e.g., Section A, Section B)
6. Re-run the demo data seeder to link students to batches

### ⚠️ Additional Employee Records
Some demo users (inventory, mess, hostel, attendance, reception) only have user accounts. To make them fully functional:
- Create contacts for them
- Create employee records
- Link to appropriate departments/designations

## How to Use Demo Accounts

### Login Credentials
All demo accounts use:
- **Email**: See table above
- **Password**: `password123`

### Testing Different Roles

1. **Principal/Manager** - Full administrative access
   ```
   Email: principal@demo.com or manager@demo.com
   Password: password123
   ```

2. **Teachers/Staff** - Academic management
   ```
   Email: staff@demo.com
   Password: password123
   ```

3. **Accountant** - Finance management
   ```
   Email: accountant@demo.com
   Password: password123
   ```

4. **Librarian** - Library management
   ```
   Email: librarian@demo.com
   Password: password123
   ```

5. **Student** - Student portal
   ```
   Email: student@demo.com
   Password: password123
   ```

6. **Guardian/Parent** - Parent portal
   ```
   Email: parent@demo.com
   Password: password123
   ```

## Re-running the Seeder

To add more demo data or fix missing records:

```bash
php artisan db:seed --class=DemoDataSeeder
```

The seeder is **idempotent** - it won't duplicate existing records, only create missing ones.

## Next Steps

1. **Create Courses & Batches** through the admin panel
2. **Add more students** if needed
3. **Test different workflows** with different role accounts
4. **Explore the system** with realistic data

## Important Notes

- ✅ All demo users bypass role validation (no "You are not allowed to login" errors)
- ✅ Employee accounts have full Contact → Employee → EmployeeRecord linkage
- ✅ Student has Contact → Registration → Admission → Student linkage (partial - needs batch)
- ✅ Guardian is linked to student through the guardians table
- ⚠️ Some users are basic accounts without full records (can still login but limited functionality)

## Troubleshooting

**Problem**: "You are not allowed to login"
**Solution**: This shouldn't happen for demo users. Verify `ValidateRole.php` has the demo user bypass.

**Problem**: Employee features not working
**Solution**: Ensure the employee has a Contact, Employee, and EmployeeRecord in the database.

**Problem**: Student features not working  
**Solution**: Student needs Course and Batch assignments. Create these through admin panel.

---

**Created**: February 24, 2026
**Seeder**: `database/seeders/DemoDataSeeder.php`
**Demo Users Seeder**: `database/seeders/DemoUsersSeeder.php`
