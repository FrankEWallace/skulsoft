# Bulk Data Import Guide - Excel Import System

## 📋 Overview
The SkulSoft School Management System has a built-in Excel import system that allows you to import large amounts of data quickly through Excel spreadsheets instead of manual entry.

> 📄 **See Also**: For exact Excel column names and formats, check **[EXCEL_IMPORT_FORMAT.md](EXCEL_IMPORT_FORMAT.md)**

## ✅ Available Import Features

### **1. Student Import**
- **Location**: Students → Import Students
- **File Format**: Excel (.xlsx, .xls) or CSV
- **What it imports**:
  - Student personal information
  - Contact details
  - Guardian information
  - Registration data
  - Admission details
  - User accounts (optional)

### **2. Employee Import**
- **Location**: Employees → Import Employees
- **File Format**: Excel (.xlsx, .xls) or CSV
- **What it imports**:
  - Employee personal information
  - Contact details
  - Department & Designation
  - Joining date
  - Employment type
  - User accounts (optional)

### **3. Other Import Features**
The system also supports importing:
- Attendance records
- Exam results
- Fee collections
- Library books
- Inventory items

---

## 🚀 How to Use Excel Import

### **Step 1: Download Template**
1. Login as **Admin** or user with import permissions
2. Navigate to the import section:
   - For Students: **Students → Import Students**
   - For Employees: **Employees → Import Employees**
3. Click **"Download Sample Template"** button
4. Save the Excel template to your computer

### **Step 2: Fill the Template**
1. Open the downloaded Excel file
2. Fill in your data following the column headers
3. **Important Rules**:
   - Don't change column headers
   - Follow the date format: `YYYY-MM-DD` (e.g., 2026-01-15)
   - Use exact values for dropdowns (e.g., gender: `male` or `female`)
   - Required fields must not be empty
   - One row = one record

### **Step 3: Upload the File**
1. Go back to the import page
2. Click **"Choose File"** or **"Browse"**
3. Select your filled Excel file
4. Click **"Upload"** or **"Import"**
5. Wait for the import to complete

### **Step 4: Review Results**
- The system will show:
  - ✅ Number of records successfully imported
  - ⚠️ Number of records skipped (duplicates)
  - ❌ Number of records with errors
- Review any error messages and fix issues if needed

---

## 📊 Student Import Template

### **Required Columns**

| Column Name | Description | Example | Required |
|------------|-------------|---------|----------|
| `first_name` | Student's first name | John | Yes |
| `last_name` | Student's last name | Doe | No |
| `gender` | Gender (male/female) | male | Yes |
| `birth_date` | Date of birth | 2015-05-20 | Yes |
| `contact_number` | Phone number | 555-1234 | Yes |
| `email` | Email address | john@example.com | No |
| `admission_number` | Admission number | ADM-2026-001 | Yes |
| `admission_date` | Date of admission | 2026-01-15 | Yes |
| `course_code` | Course/Class code | G3 | Yes |
| `batch_code` | Section/Batch code | A | Yes |
| `roll_number` | Roll number | 001 | No |
| `father_name` | Father's name | Mr. Doe | No |
| `mother_name` | Mother's name | Mrs. Doe | No |
| `guardian_contact` | Guardian phone | 555-5678 | No |
| `address_line1` | Address | 123 Main St | No |
| `city` | City | New York | No |
| `state` | State | NY | No |
| `zipcode` | ZIP/Postal code | 10001 | No |

### **Optional: Create User Account**

| Column Name | Description | Example | Required |
|------------|-------------|---------|----------|
| `username` | Login username | john.doe | No |
| `password` | Login password | password123 | No |

**Note**: If username and password are provided, a user account will be created automatically.

---

## 👥 Employee Import Template

### **Required Columns**

| Column Name | Description | Example | Required |
|------------|-------------|---------|----------|
| `first_name` | Employee first name | Sarah | Yes |
| `last_name` | Employee last name | Smith | No |
| `gender` | Gender (male/female) | female | Yes |
| `birth_date` | Date of birth | 1990-03-15 | Yes |
| `contact_number` | Phone number | 555-9876 | Yes |
| `email` | Email address | sarah@school.com | Yes |
| `employee_code` | Employee code | EMP-2026-001 | Yes |
| `joining_date` | Joining date | 2026-01-01 | Yes |
| `designation_name` | Designation | Teacher | Yes |
| `department_name` | Department | Academics | Yes |
| `employment_type` | Type (permanent/contract) | permanent | Yes |
| `address_line1` | Address | 456 Oak Ave | No |
| `city` | City | Boston | No |
| `state` | State | MA | No |
| `zipcode` | ZIP code | 02101 | No |

### **Optional: Create User Account**

| Column Name | Description | Example | Required |
|------------|-------------|---------|----------|
| `username` | Login username | sarah.smith | No |
| `password` | Login password | welcome123 | No |
| `role` | User role | staff | No |

---

## 📝 Important Tips

### **Data Validation**
- **Dates**: Use format `YYYY-MM-DD` (e.g., 2026-01-15)
- **Gender**: Exactly `male` or `female` (lowercase)
- **Email**: Must be valid email format
- **Phone**: Numeric values, can include dashes/spaces
- **Codes**: Must be unique if specified

### **Avoiding Duplicates**
- System checks for existing records by:
  - **Students**: Admission number, contact number
  - **Employees**: Employee code, email
- Duplicates are skipped automatically
- Check import history to see skipped records

### **Handling Errors**
Common errors and solutions:

| Error | Solution |
|-------|----------|
| Invalid date format | Use YYYY-MM-DD format |
| Email already exists | Use different email or leave blank |
| Course not found | Create course first or check code |
| Department not found | Create department first |
| Missing required field | Fill all required columns |

### **Best Practices**
1. ✅ **Start small**: Test with 5-10 records first
2. ✅ **Check template**: Download latest template each time
3. ✅ **Backup data**: Keep original Excel file
4. ✅ **Review errors**: Fix and re-import failed records
5. ✅ **Use consistent codes**: Keep course/department codes consistent

---

## 🔧 Prerequisites

### **Before Importing Students**
Make sure these exist in the system:
- [ ] Academic Period (e.g., 2025-2026)
- [ ] Courses/Classes (e.g., Grade 1, Grade 2)
- [ ] Batches/Sections (e.g., Section A, Section B)
- [ ] Enrollment types (if required)

**How to create:**
1. Login as Admin
2. Navigate to **Academic → Periods**
3. Create period if not exists
4. Navigate to **Academic → Courses**
5. Create courses
6. Navigate to **Academic → Batches**
7. Create batches for each course

### **Before Importing Employees**
Make sure these exist in the system:
- [ ] Departments (e.g., Academics, Administration)
- [ ] Designations (e.g., Teacher, Principal)

**How to create:**
1. Login as Admin
2. Navigate to **Employee → Departments**
3. Create departments
4. Navigate to **Employee → Designations**
5. Create designations

---

## 📂 Import History & Rollback

### **View Import History**
- **Students**: Students → Import History
- **Employees**: Employees → Import History

### **What you can see:**
- Date and time of import
- Number of records imported
- Import UUID
- Imported by user
- Download original file

### **Rollback/Delete Import**
⚠️ **Admin only**
1. Go to Import History
2. Find the import batch
3. Click **Delete** to remove all records from that import
4. Confirm deletion

**Warning**: This will permanently delete all records from that import batch!

---

## 💡 Example Workflow

### **Importing 100 Students**

1. **Prepare System** (one-time setup)
   ```
   - Create Period: "2025-2026"
   - Create Courses: Grade 1, Grade 2, Grade 3
   - Create Batches: Section A, Section B for each course
   ```

2. **Download Template**
   ```
   Students → Import Students → Download Sample Template
   ```

3. **Fill Excel File**
   ```
   Row 1: Headers (don't change)
   Row 2: John Doe, male, 2015-05-20, ADM-001, G1, A, ...
   Row 3: Jane Smith, female, 2015-08-15, ADM-002, G1, A, ...
   ...
   Row 101: Last student data
   ```

4. **Import**
   ```
   Students → Import Students → Choose File → Upload
   ```

5. **Review**
   ```
   ✅ 98 records imported successfully
   ⚠️ 2 records skipped (duplicates)
   ```

6. **Verify**
   ```
   Students → All Students
   Filter by import batch UUID
   Check if all students appear correctly
   ```

---

## 🆘 Troubleshooting

### **Import fails completely**
- Check file format (must be .xlsx or .xls)
- Ensure column headers match template exactly
- Check for special characters in data
- Verify file is not corrupted

### **Some records skipped**
- Check import history for details
- Look for duplicate admission numbers
- Verify required fields are filled
- Check date formats

### **Created but missing data**
- Some optional fields may be blank
- Check if courses/batches exist
- Verify department/designation names match exactly

### **User accounts not created**
- Ensure username and password columns are filled
- Check if email already exists in system
- Verify username is unique

---

## 📞 Support

Need help with bulk import?
1. Check this guide first
2. Review import history for error details
3. Contact your system administrator
4. Check the main documentation: `COMPLETE_DOCUMENTATION.md`

---

**Created**: February 24, 2026
**Version**: 1.0
**System**: SkulSoft School Management System
