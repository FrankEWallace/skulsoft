# Attendance System Configuration Guide

## Overview

The SkulSoft School Management System includes a comprehensive attendance tracking system for both **Students** and **Employees**. The system supports multiple attendance methods, real-time tracking, and automated notifications.

---

## Table of Contents

1. [Student Attendance System](#student-attendance-system)
2. [Employee Attendance System](#employee-attendance-system)
3. [Attendance Configuration](#attendance-configuration)
4. [Attendance Methods](#attendance-methods)
5. [Permissions & Roles](#permissions--roles)
6. [API Endpoints](#api-endpoints)
7. [Database Structure](#database-structure)
8. [How to Configure](#how-to-configure)

---

## Student Attendance System

### Features

- **Batch-wise Attendance**: Mark attendance for entire batch/class
- **Subject-wise Attendance**: Mark attendance for specific subjects
- **Session-based Tracking**: Multiple sessions per day (First, Second, Both)
- **Attendance Types**: Present, Absent, Late, Half Day, Early Leaving
- **Past Date Limit**: Configurable limit for marking past attendance
- **Holiday Detection**: Automatic holiday detection and marking
- **Notifications**: Automatic SMS/Email notifications to students/guardians
- **QR Code Support**: QR code-based attendance marking

### Attendance Types

| Code | Type | Description | Color |
|------|------|-------------|-------|
| **P** | Present | Student is present | Green (Success) |
| **A** | Absent | Student is absent | Red (Danger) |
| **L** | Late | Student arrived late | Yellow (Warning) |
| **HD** | Half Day | Student attended half day | Blue (Info) |
| **EL** | Early Leaving | Student left early | Blue (Primary) |

### Attendance Sessions

Students can have attendance marked in different sessions:

- **FIRST** - First session (e.g., morning)
- **SECOND** - Second session (e.g., afternoon)
- **BOTH** - Full day attendance

### Student Attendance Workflow

1. **Select Method**
   - Batch-wise (mark all students in a batch)
   - Subject-wise (mark for specific subject)

2. **Select Date & Session**
   - Choose date within allowed range
   - Select session (if subject-wise)

3. **Mark Attendance**
   - Select batch/subject
   - Mark each student's status
   - Add remarks if needed

4. **Submit**
   - Attendance is saved
   - Notifications sent automatically

### Database Table: `student_attendances`

```sql
CREATE TABLE student_attendances (
    id BIGINT PRIMARY KEY,
    uuid VARCHAR(36) UNIQUE,
    date DATE,
    batch_id BIGINT (FK -> batches),
    subject_id BIGINT (FK -> subjects) NULLABLE,
    session VARCHAR(50), -- 'first', 'second', 'both'
    is_default BOOLEAN, -- true for batch-wise, false for subject-wise
    values JSON, -- Array of student attendance data
    meta JSON, -- Additional metadata
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Values JSON Structure:**
```json
[
    {
        "student_id": 1,
        "code": "P",
        "remarks": ""
    },
    {
        "student_id": 2,
        "code": "A",
        "remarks": "Sick"
    }
]
```

---

## Employee Attendance System

### Features

- **Attendance Types**: Customizable attendance types (Present, Leave, Work from Home, etc.)
- **Time-based Tracking**: Clock in/clock out with timesheet
- **Production-based**: Track attendance by production units
- **Work Shifts**: Assign and track work shifts
- **Timesheet Management**: Detailed time tracking with breaks
- **Bulk Import**: Import attendance via Excel/CSV
- **Automated Calculation**: Auto-calculate working hours

### Employee Attendance Types

Attendance types are fully customizable per team. Common types include:

| Category | Examples | Unit |
|----------|----------|------|
| **Present** | Full Day, Half Day | Days |
| **Leave** | Sick Leave, Casual Leave, Annual Leave | Days |
| **Special** | Work from Home, On-site | Days |
| **Production** | Per Unit, Per Hour | Units/Hours |

### Attendance Categories

```php
// Defined in App\Enums\Employee\Attendance\Category
- PRESENT
- ABSENT
- LEAVE
- HALF_DAY
- PRODUCTION_BASED_DAILY
- PRODUCTION_BASED_MONTHLY
```

### Database Tables

#### `employee_attendances`
```sql
CREATE TABLE employee_attendances (
    id BIGINT PRIMARY KEY,
    uuid VARCHAR(36) UNIQUE,
    employee_id BIGINT (FK -> employees),
    attendance_type_id BIGINT (FK -> attendance_types),
    attendance_symbol VARCHAR(10),
    date DATE,
    is_time_based BOOLEAN,
    remarks TEXT,
    config JSON,
    meta JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(date, employee_id)
);
```

#### `attendance_types`
```sql
CREATE TABLE attendance_types (
    id BIGINT PRIMARY KEY,
    uuid VARCHAR(36) UNIQUE,
    team_id BIGINT (FK -> teams),
    name VARCHAR(255),
    code VARCHAR(50) UNIQUE,
    alias VARCHAR(50),
    description TEXT,
    category VARCHAR(50), -- from AttendanceCategory enum
    unit VARCHAR(50), -- 'day', 'hour', 'unit'
    color VARCHAR(20),
    meta JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## Attendance Configuration

### Student Attendance Settings

Configuration is stored in the config table or config files:

1. **Past Day Limit**
   ```php
   config('config.student.attendance_past_day_limit', 0)
   ```
   - Default: 0 days
   - Controls how many days back attendance can be marked
   - Example: If set to 7, can mark attendance up to 7 days ago

2. **Auto Notifications**
   ```php
   config('config.student.send_attendance_notification', false)
   ```
   - Enable/disable automatic notifications
   - Sends SMS/Email to students and guardians

3. **Session Configuration**
   - Defined in `App\Enums\Student\AttendanceSession`
   - Can be configured per institution needs

### Employee Attendance Settings

1. **Work Shifts**
   - Create work shifts with start/end times
   - Assign shifts to employees
   - Configure grace periods

2. **Attendance Types**
   - Navigate to: Employee → Attendance → Attendance Types
   - Create custom attendance types
   - Set categories and units

3. **Timesheet Settings**
   - Clock in/out functionality
   - Break time tracking
   - Overtime calculation

---

## Attendance Methods

### Method 1: Manual Entry

**Student Attendance:**
```
1. Go to: Students → Attendance
2. Select method (Batch-wise or Subject-wise)
3. Choose date and batch
4. Mark each student's attendance
5. Submit
```

**Employee Attendance:**
```
1. Go to: Employee → Attendance → Mark Attendance
2. Select date
3. Choose attendance type for each employee
4. Add remarks if needed
5. Submit
```

### Method 2: QR Code (Students)

**Setup:**
```
1. Enable QR code attendance
2. Generate unique QR codes for batches
3. Students scan QR code on entry
4. Attendance marked automatically
```

**API Endpoint:**
```
POST /api/v1/app/attendance/qr-code
```

### Method 3: Timesheet (Employees)

**Employee Self-Service:**
```
1. Employee logs in
2. Navigate to: My Attendance → Timesheet
3. Click "Clock In" at start of day
4. Click "Clock Out" at end of day
5. System calculates working hours
```

**API Endpoints:**
```
GET  /api/v1/app/employee/attendance/timesheet/check
POST /api/v1/app/employee/attendance/timesheet/clock
```

### Method 4: Bulk Import (Employees)

**Import from Excel/CSV:**
```
1. Download template
2. Fill in attendance data
3. Upload file
4. Review and confirm
5. Import processed
```

**API Endpoint:**
```
POST /api/v1/app/employee/attendance/timesheets/import
```

---

## Permissions & Roles

### Student Attendance Permissions

From `/resources/var/permission.json`:

```json
{
    "student:list-attendance": [
        "manager",
        "principal",
        "staff",
        "attendance-assistant"
    ],
    "student:mark-attendance": [
        "manager",
        "principal",
        "staff",
        "attendance-assistant"
    ]
}
```

### Employee Attendance Permissions

```json
{
    "employee:list-attendance": ["manager", "principal"],
    "employee:mark-attendance": ["manager", "principal"],
    "employee:timesheet": ["staff", "employee"]
}
```

### Roles with Attendance Access

- **Manager**: Full attendance management
- **Principal**: Full attendance management
- **Staff**: Can mark own attendance
- **Attendance Assistant**: Dedicated attendance marking
- **Accountant**: View attendance reports
- **Student**: View own attendance
- **Guardian**: View child's attendance

---

## API Endpoints

### Student Attendance

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/app/student/attendance/pre-requisite` | Get attendance types & settings |
| GET | `/api/v1/app/student/attendance/fetch` | Fetch attendance records |
| POST | `/api/v1/app/student/attendance` | Mark attendance |
| DELETE | `/api/v1/app/student/attendance` | Remove attendance |
| POST | `/api/v1/app/student/attendance/notification` | Send notifications |
| POST | `/api/v1/app/attendance/qr-code` | QR code attendance |

### Employee Attendance

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/app/employee/attendance/pre-requisite` | Get prerequisites |
| GET | `/api/v1/app/employee/attendance/fetch` | Fetch records |
| POST | `/api/v1/app/employee/attendance/mark` | Mark attendance |
| GET | `/api/v1/app/employee/attendance/types` | List attendance types |
| POST | `/api/v1/app/employee/attendance/types` | Create attendance type |
| GET | `/api/v1/app/employee/attendance/timesheets` | List timesheets |
| POST | `/api/v1/app/employee/attendance/timesheet/clock` | Clock in/out |
| POST | `/api/v1/app/employee/attendance/timesheets/import` | Import timesheets |

### Exam Attendance

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/app/exam/attendance/pre-requisite` | Get prerequisites |
| GET | `/api/v1/app/exam/attendance/fetch` | Fetch exam attendance |
| POST | `/api/v1/app/exam/attendance` | Mark exam attendance |
| DELETE | `/api/v1/app/exam/attendance` | Remove exam attendance |

---

## How to Configure

### Step 1: Configure Student Attendance Settings

**Via Admin Panel:**
```
1. Login as Admin/Manager
2. Navigate to: Configuration → Student
3. Find "Attendance Settings"
4. Set:
   - Past day limit (days)
   - Auto notification (yes/no)
   - Default session
5. Save changes
```

**Via Database:**
```sql
-- Update configuration
UPDATE config 
SET value = '7' 
WHERE name = 'attendance_past_day_limit';
```

### Step 2: Create Employee Attendance Types

**Via Admin Panel:**
```
1. Navigate to: Employee → Attendance → Attendance Types
2. Click "Add New Type"
3. Fill in:
   - Name: e.g., "Full Day"
   - Code: e.g., "FD"
   - Category: Select from dropdown
   - Unit: Days/Hours/Units
   - Color: Choose color
4. Save
```

**Example Attendance Types to Create:**

| Name | Code | Category | Unit | Description |
|------|------|----------|------|-------------|
| Full Day | FD | Present | Day | Full day present |
| Half Day | HD | Half Day | Day | Half day present |
| Sick Leave | SL | Leave | Day | Sick leave |
| Casual Leave | CL | Leave | Day | Casual leave |
| Work from Home | WFH | Present | Day | Working remotely |

### Step 3: Configure Work Shifts

```
1. Navigate to: Employee → Attendance → Work Shifts
2. Click "Add Work Shift"
3. Set:
   - Shift name
   - Start time
   - End time
   - Break duration
   - Grace period
4. Assign to employees
```

### Step 4: Set Up Notifications

**Email Template:**
```
Location: Configuration → Email Templates
Template Code: student-daily-attendance-report

Available Variables:
- ##NAME##
- ##DATE##
- ##ATTENDANCE## (Present/Absent/Late)
- ##COURSE_NAME##
- ##BATCH_NAME##
```

**SMS Template:**
```
Location: Configuration → SMS Templates
Template Code: student-daily-attendance-report

Example:
"Hi ##NAME##, Your attendance is marked as ##ATTENDANCE## on ##DATE## for ##COURSE_NAME## ##BATCH_NAME##."
```

### Step 5: Test the System

**Test Student Attendance:**
```bash
# Via Tinker
php artisan tinker

# Mark test attendance
>>> $batch = \App\Domain\Academic\Models\Batch::first();
>>> $attendance = \App\Models\Student\Attendance::create([
    'date' => today(),
    'batch_id' => $batch->id,
    'session' => 'first',
    'is_default' => true,
    'values' => [
        ['student_id' => 1, 'code' => 'P', 'remarks' => ''],
        ['student_id' => 2, 'code' => 'A', 'remarks' => 'Sick']
    ]
]);
```

**Test Employee Attendance:**
```bash
# Via Tinker
>>> $employee = \App\Models\Employee\Employee::first();
>>> $type = \App\Models\Employee\Attendance\Type::first();
>>> $attendance = \App\Models\Employee\Attendance\Attendance::create([
    'employee_id' => $employee->id,
    'attendance_type_id' => $type->id,
    'date' => today(),
    'attendance_symbol' => $type->code
]);
```

---

## Reports & Analytics

### Available Reports

1. **Student Attendance Report**
   - Daily attendance summary
   - Monthly attendance percentage
   - Batch-wise analysis
   - Subject-wise attendance
   - Student-wise detailed report

2. **Employee Attendance Report**
   - Daily attendance register
   - Monthly summary
   - Leave balance report
   - Working hours report
   - Overtime calculation

### Export Options

- PDF
- Excel
- CSV
- Print

---

## Troubleshooting

### Common Issues

1. **Cannot mark past attendance**
   - Check `attendance_past_day_limit` setting
   - Increase the limit if needed

2. **Notifications not sending**
   - Verify email/SMS configuration
   - Check template is enabled
   - Ensure notification queue is running

3. **QR code not working**
   - Verify QR code feature is enabled
   - Check network connectivity
   - Ensure correct batch/date selection

4. **Timesheet clock in/out fails**
   - Check employee has assigned work shift
   - Verify time is within shift hours
   - Check for duplicate entries

---

## Best Practices

1. **Daily Attendance**
   - Mark attendance daily
   - Use batch-wise for efficiency
   - Review before submitting

2. **Employee Timesheets**
   - Encourage self-service
   - Regular reconciliation
   - Monitor overtime

3. **Notifications**
   - Enable for absent students
   - Send summary reports weekly
   - Customize templates

4. **Backup**
   - Regular database backups
   - Export monthly reports
   - Archive old data

---

## Summary

### Attendance System Capabilities

**Student Attendance:**
- Multiple marking methods (Batch, Subject, QR Code)
- 5 attendance types (P, A, L, HD, EL)
- Session-based tracking
- Automated notifications
- Configurable past date limits
- Holiday integration

**Employee Attendance:**
- Customizable attendance types
- Time-based tracking with timesheets
- Work shift management
- Production-based attendance
- Bulk import capability
- Self-service clock in/out

**Both Systems Support:**
- Role-based access control
- Comprehensive reporting
- API integration
- Mobile-friendly
- Multi-tenant (team-based)

---

**Date Created**: February 23, 2026  
**System**: SkulSoft School Management System  
**Modules**: Student Attendance, Employee Attendance, Exam Attendance  
**Database**: Multi-tenant attendance tracking system
