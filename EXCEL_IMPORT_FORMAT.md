# Excel Import Template - Exact Column Names

## 📋 STUDENT IMPORT TEMPLATE

### **Exact Column Headers (Case-Sensitive)**

Copy these **exact** column names into your Excel file (Row 1):

```
first_name | middle_name | last_name | gender | date_of_birth | contact_number | email | father_name | mother_name | blood_group | category | caste | religion | enrollment_type | address | address_line1 | address_line2 | city | state | zipcode | country | unique_id1 | unique_id2 | unique_id3 | unique_id4 | unique_id5 | nationality | mother_tongue | birth_place | alternate_contact_number | alternate_email | date_of_admission | admission_number | course | batch | roll_number | admission_type | username | password
```

### **Column Details**

| Column Name | Required | Format | Example | Notes |
|------------|----------|--------|---------|-------|
| **first_name** | ✅ Yes | Text (1-100 chars) | John | Student's first name |
| **middle_name** | No | Text (max 100) | Peter | Middle name |
| **last_name** | No | Text (max 100) | Doe | Last name |
| **gender** | ✅ Yes | `male` or `female` | male | Lowercase only |
| **date_of_birth** | No | YYYY-MM-DD or Excel date | 2015-05-20 | Birth date |
| **contact_number** | ✅ Yes | Text (max 20) | 555-1234 | Phone number |
| **email** | No | Valid email | john@demo.com | Email address |
| **father_name** | No | Text (2-100) | Mr. John Doe | Father's name |
| **mother_name** | No | Text (2-100) | Mrs. Jane Doe | Mother's name |
| **blood_group** | No | A+, A-, B+, B-, O+, O-, AB+, AB- | A+ | Blood group |
| **category** | No | Must match existing | General | Category name |
| **caste** | No | Must match existing | OBC | Caste name |
| **religion** | No | Must match existing | Hindu | Religion name |
| **enrollment_type** | No | Must match existing | Regular | Enrollment type |
| **address** | No | Text (max 250) | 123 Main St | Full address (alternative to address_line1) |
| **address_line1** | No | Text (max 250) | 123 Main Street | Address line 1 |
| **address_line2** | No | Text (max 100) | Apt 4B | Address line 2 |
| **city** | No | Text (max 50) | New York | City name |
| **state** | No | Text (max 50) | NY | State/Province |
| **zipcode** | No | Text (max 10) | 10001 | ZIP/Postal code |
| **country** | No | Text (max 20) | USA | Country |
| **unique_id1** | No | Text | ID123456 | National ID, etc. |
| **unique_id2** | No | Text | PASS789 | Passport, etc. |
| **unique_id3** | No | Text | - | Other ID |
| **unique_id4** | No | Text | - | Other ID |
| **unique_id5** | No | Text | - | Other ID |
| **nationality** | No | Text | American | Nationality |
| **mother_tongue** | No | Text | English | First language |
| **birth_place** | No | Text | New York | Birth place |
| **alternate_contact_number** | No | Text (max 20) | 555-9999 | Alternate phone |
| **alternate_email** | No | Valid email | alt@demo.com | Alternate email |
| **date_of_admission** | ✅ Yes | YYYY-MM-DD or Excel date | 2026-01-15 | Admission date |
| **admission_number** | No | Text | ADM-2026-001 | Admission number |
| **course** | ✅ Yes | Must match existing course | Grade 3 | Course/Class name |
| **batch** | ✅ Yes | Must match existing batch | Section A | Batch/Section name |
| **roll_number** | No | Text/Number | 001 | Roll number |
| **admission_type** | No | `new` or `old` | new | New = fresh admission |
| **username** | No | Text (unique) | john.doe | Login username |
| **password** | No | Text | password123 | Login password |

---

## 👥 EMPLOYEE IMPORT TEMPLATE

### **Exact Column Headers (Case-Sensitive)**

Copy these **exact** column names into your Excel file (Row 1):

```
first_name | middle_name | last_name | gender | date_of_birth | contact_number | email | type | date_of_joining | employee_code | employee_code_format | department | designation | employment_status | unique_id1 | unique_id2 | unique_id3 | unique_id4 | unique_id5 | nationality | mother_tongue | birth_place | address_line1 | address_line2 | city | state | zipcode | country | alternate_contact_number | alternate_email | username | password
```

### **Column Details**

| Column Name | Required | Format | Example | Notes |
|------------|----------|--------|---------|-------|
| **first_name** | ✅ Yes | Text (1-100) | Sarah | First name |
| **middle_name** | No | Text (max 100) | Jane | Middle name |
| **last_name** | No | Text (max 100) | Smith | Last name |
| **gender** | ✅ Yes | `male` or `female` | female | Lowercase only |
| **date_of_birth** | ✅ Yes | YYYY-MM-DD or Excel date | 1985-03-15 | Birth date |
| **contact_number** | ✅ Yes | Text (max 20) | 555-5678 | Phone number |
| **email** | ✅ Yes | Valid email (unique) | sarah@school.com | Email address |
| **type** | ✅ Yes | `permanent` or `contract` | permanent | Employment type |
| **date_of_joining** | ✅ Yes | YYYY-MM-DD or Excel date | 2026-01-01 | Joining date |
| **employee_code** | ✅ Yes | Text (unique) | EMP-001 | Employee code |
| **employee_code_format** | No | Text | EMP-%NUMBER% | Code format |
| **department** | ✅ Yes | Must match existing | Academics | Department name |
| **designation** | ✅ Yes | Must match existing | Teacher | Designation name |
| **employment_status** | No | Must match existing | Active | Employment status |
| **unique_id1** | No | Text | SSN123 | National ID |
| **unique_id2** | No | Text | PASS456 | Passport |
| **unique_id3** | No | Text | - | Other ID |
| **unique_id4** | No | Text | - | Other ID |
| **unique_id5** | No | Text | - | Other ID |
| **nationality** | No | Text | American | Nationality |
| **mother_tongue** | No | Text | English | First language |
| **birth_place** | No | Text | Boston | Birth place |
| **address_line1** | No | Text (max 250) | 456 Oak Ave | Address line 1 |
| **address_line2** | No | Text (max 100) | Suite 12 | Address line 2 |
| **city** | No | Text (max 50) | Boston | City |
| **state** | No | Text (max 50) | MA | State |
| **zipcode** | No | Text (max 10) | 02101 | ZIP code |
| **country** | No | Text (max 20) | USA | Country |
| **alternate_contact_number** | No | Text (max 20) | 555-8888 | Alternate phone |
| **alternate_email** | No | Valid email | alt@school.com | Alternate email |
| **username** | No | Text (unique) | sarah.smith | Login username |
| **password** | No | Text | welcome123 | Login password |

---

## 📝 SAMPLE DATA

### **Student Import Sample (Row 2)**

```
John | Peter | Doe | male | 2015-05-20 | 555-1234 | john@demo.com | Mr. John Doe Sr | Mrs. Jane Doe | A+ | General | | Hindu | Regular | | 123 Main Street | | New York | NY | 10001 | USA | | | | | | American | English | New York | 555-5555 | | 2026-01-15 | ADM-2026-001 | Grade 3 | Section A | 001 | new | john.doe | password123
```

### **Employee Import Sample (Row 2)**

```
Sarah | Jane | Smith | female | 1985-03-15 | 555-5678 | sarah@school.com | permanent | 2026-01-01 | EMP-001 | | Academics | Teacher | Active | | | | | | American | English | Boston | 456 Oak Avenue | Suite 12 | Boston | MA | 02101 | USA | 555-8888 | | sarah.smith | welcome123
```

---

## ⚠️ CRITICAL RULES

### **1. Column Headers Must Be EXACT**
- Use lowercase
- Use underscores (not spaces)
- No typos
- Headers are in Row 1

### **2. Date Formats**
✅ **Accepted formats:**
- `YYYY-MM-DD` (e.g., 2026-01-15)
- Excel date format (numeric value)

❌ **NOT accepted:**
- DD/MM/YYYY
- MM/DD/YYYY
- Text dates (e.g., "January 15, 2026")

### **3. Gender Values**
- Must be exactly: `male` or `female`
- **Lowercase only**
- No other values accepted

### **4. Employment Type (Employees only)**
- Must be exactly: `permanent` or `contract`
- **Lowercase only**

### **5. Matching Values**
These must match **exactly** what exists in your system:
- **course** - Must match Course name (e.g., "Grade 3")
- **batch** - Must match Batch name (e.g., "Section A")
- **department** - Must match Department name
- **designation** - Must match Designation name
- **category**, **caste**, **religion**, **enrollment_type** - Must match Option names

### **6. Unique Values**
These must be unique (no duplicates):
- **admission_number** (students)
- **employee_code** (employees)
- **email** (both)
- **username** (both, if provided)

### **7. User Account Creation**
To create login accounts:
- Provide both `username` AND `password`
- If either is missing, no user account created
- Username must be unique
- Email must be unique

---

## 🎯 QUICK START TEMPLATE

### **Minimal Student Import (Required fields only)**

Headers:
```
first_name | gender | contact_number | date_of_admission | course | batch
```

Sample Data:
```
John Doe | male | 555-1234 | 2026-01-15 | Grade 3 | Section A
Jane Smith | female | 555-5678 | 2026-01-15 | Grade 3 | Section A
```

### **Minimal Employee Import (Required fields only)**

Headers:
```
first_name | gender | date_of_birth | contact_number | email | type | date_of_joining | employee_code | department | designation
```

Sample Data:
```
Sarah Smith | female | 1985-03-15 | 555-5678 | sarah@school.com | permanent | 2026-01-01 | EMP-001 | Academics | Teacher
John Brown | male | 1980-05-20 | 555-9999 | john@school.com | permanent | 2026-01-01 | EMP-002 | Administration | Principal
```

---

## 📊 HOW TO CREATE YOUR EXCEL FILE

### **Option 1: Manual Creation**

1. Open Excel (or Google Sheets)
2. In Row 1, paste the exact column headers (see above)
3. In Row 2+, enter your data
4. Save as `.xlsx` or `.xls` format
5. Import through the system

### **Option 2: Download System Template**

1. Login to the system
2. Go to Students → Import or Employees → Import
3. Click "Download Sample Template"
4. Fill in the template
5. Upload

---

## 💾 SAVE FORMAT

**Supported file formats:**
- ✅ `.xlsx` (Excel 2007+)
- ✅ `.xls` (Excel 97-2003)
- ✅ `.csv` (Comma-separated values)

**File size limits:**
- Students: 1000 records per file
- Employees: 200 records per file

---

## ✅ VALIDATION CHECKLIST

Before importing, verify:

- [ ] Column headers are exact (case-sensitive)
- [ ] All required fields are filled
- [ ] Dates are in YYYY-MM-DD format
- [ ] Gender is `male` or `female` (lowercase)
- [ ] Course and Batch names exist in system
- [ ] Department and Designation names exist in system
- [ ] Email addresses are unique
- [ ] No duplicate admission numbers or employee codes
- [ ] File is saved as .xlsx, .xls, or .csv
- [ ] Row 1 has headers, Row 2+ has data

---

**Last Updated**: February 24, 2026
**System**: SkulSoft School Management System
