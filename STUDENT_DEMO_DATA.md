# 🎓 Student Demo Data Documentation

## Overview
This document describes the comprehensive student demo data that has been created for the SkulSoft School Management System.

## Summary

### Academic Structure
- **Division**: Primary Section
- **Courses**: 6 grades (Grade 1 through Grade 6)
- **Batches**: 12 sections (2 sections per grade - Section A and Section B)
- **Students**: 14 students across all grades

## Course & Batch Structure

| Course | Code | Batches |
|--------|------|---------|
| Grade 1 | GR1 | Section A, Section B |
| Grade 2 | GR2 | Section A, Section B |
| Grade 3 | GR3 | Section A, Section B |
| Grade 4 | GR4 | Section A, Section B |
| Grade 5 | GR5 | Section A, Section B |
| Grade 6 | GR6 | Section A, Section B |

## Student Details

### Grade 1 (3 students)

#### Emma Johnson
- **Email**: emma.johnson@demo.com
- **Gender**: Female
- **Birth Date**: March 15, 2020
- **Blood Group**: A+
- **Batch**: Section A
- **Roll Number**: 001
- **Admission Number**: ADM2026001
- **Father**: Robert Johnson
- **Mother**: Mary Johnson
- **Contact**: 555-1001
- **Address**: 123 Oak Street, Springfield, Illinois 62701

#### Liam Smith
- **Email**: liam.smith@demo.com
- **Gender**: Male
- **Birth Date**: May 22, 2020
- **Blood Group**: B+
- **Batch**: Section A
- **Roll Number**: 002
- **Admission Number**: ADM2026002
- **Father**: James Smith
- **Mother**: Patricia Smith
- **Contact**: 555-1002
- **Address**: 456 Maple Avenue, Springfield, Illinois 62702

#### Olivia Williams
- **Email**: olivia.williams@demo.com
- **Gender**: Female
- **Birth Date**: January 10, 2020
- **Blood Group**: O+
- **Batch**: Section B
- **Roll Number**: 001
- **Admission Number**: ADM2026003
- **Father**: Michael Williams
- **Mother**: Linda Williams
- **Contact**: 555-1003
- **Address**: 789 Pine Road, Springfield, Illinois 62703

### Grade 2 (2 students)

#### Noah Brown
- **Email**: noah.brown@demo.com
- **Gender**: Male
- **Birth Date**: August 18, 2019
- **Blood Group**: AB+
- **Batch**: Section A
- **Roll Number**: 001
- **Admission Number**: ADM2025001
- **Father**: David Brown
- **Mother**: Jennifer Brown
- **Contact**: 555-1004
- **Address**: 321 Elm Street, Springfield, Illinois 62704

#### Ava Jones
- **Email**: ava.jones@demo.com
- **Gender**: Female
- **Birth Date**: November 30, 2019
- **Blood Group**: A-
- **Batch**: Section A
- **Roll Number**: 002
- **Admission Number**: ADM2025002
- **Father**: William Jones
- **Mother**: Elizabeth Jones
- **Contact**: 555-1005
- **Address**: 654 Birch Lane, Springfield, Illinois 62705

### Grade 3 (2 students)

#### Ethan Garcia
- **Email**: ethan.garcia@demo.com
- **Gender**: Male
- **Birth Date**: April 25, 2018
- **Blood Group**: B-
- **Batch**: Section A
- **Roll Number**: 001
- **Admission Number**: ADM2024001
- **Father**: Carlos Garcia
- **Mother**: Maria Garcia
- **Contact**: 555-1006
- **Address**: 987 Cedar Court, Springfield, Illinois 62706

#### Sophia Martinez
- **Email**: sophia.martinez@demo.com
- **Gender**: Female
- **Birth Date**: September 12, 2018
- **Blood Group**: O-
- **Batch**: Section A
- **Roll Number**: 002
- **Admission Number**: ADM2024002
- **Father**: Jose Martinez
- **Mother**: Carmen Martinez
- **Contact**: 555-1007
- **Address**: 147 Willow Drive, Springfield, Illinois 62707

### Grade 4 (2 students)

#### Mason Rodriguez
- **Email**: mason.rodriguez@demo.com
- **Gender**: Male
- **Birth Date**: February 28, 2017
- **Blood Group**: A+
- **Batch**: Section A
- **Roll Number**: 001
- **Admission Number**: ADM2023001
- **Father**: Juan Rodriguez
- **Mother**: Rosa Rodriguez
- **Contact**: 555-1008
- **Address**: 258 Spruce Avenue, Springfield, Illinois 62708

#### Isabella Davis
- **Email**: isabella.davis@demo.com
- **Gender**: Female
- **Birth Date**: July 14, 2017
- **Blood Group**: B+
- **Batch**: Section A
- **Roll Number**: 002
- **Admission Number**: ADM2023002
- **Father**: Thomas Davis
- **Mother**: Sarah Davis
- **Contact**: 555-1009
- **Address**: 369 Ash Boulevard, Springfield, Illinois 62709

### Grade 5 (3 students)

#### Lucas Miller
- **Email**: lucas.miller@demo.com
- **Gender**: Male
- **Birth Date**: December 5, 2016
- **Blood Group**: O+
- **Batch**: Section A
- **Roll Number**: 001
- **Admission Number**: ADM2022001
- **Father**: Daniel Miller
- **Mother**: Nancy Miller
- **Contact**: 555-1010
- **Address**: 741 Poplar Street, Springfield, Illinois 62710

#### Mia Wilson
- **Email**: mia.wilson@demo.com
- **Gender**: Female
- **Birth Date**: June 20, 2016
- **Blood Group**: AB-
- **Batch**: Section A
- **Roll Number**: 002
- **Admission Number**: ADM2022002
- **Father**: Christopher Wilson
- **Mother**: Karen Wilson
- **Contact**: 555-1011
- **Address**: 852 Hickory Lane, Springfield, Illinois 62711

#### Tom Student (Demo User)
- **Email**: student@demo.com
- **Gender**: Male
- **Birth Date**: February 24, 2016
- **Blood Group**: O+
- **Batch**: Section B
- **Roll Number**: 001
- **Admission Number**: ADM2022003
- **Father**: Mr. Student Senior
- **Mother**: Mrs. Student
- **Contact**: 555-1234
- **Address**: 555 Demo Street, Springfield, Illinois 62714
- **Login**: student@demo.com / password123

### Grade 6 (2 students)

#### Alexander Moore
- **Email**: alexander.moore@demo.com
- **Gender**: Male
- **Birth Date**: October 8, 2015
- **Blood Group**: A+
- **Batch**: Section A
- **Roll Number**: 001
- **Admission Number**: ADM2021001
- **Father**: Richard Moore
- **Mother**: Betty Moore
- **Contact**: 555-1012
- **Address**: 963 Walnut Road, Springfield, Illinois 62712

#### Charlotte Taylor
- **Email**: charlotte.taylor@demo.com
- **Gender**: Female
- **Birth Date**: March 16, 2015
- **Blood Group**: B+
- **Batch**: Section A
- **Roll Number**: 002
- **Admission Number**: ADM2021002
- **Father**: Matthew Taylor
- **Mother**: Margaret Taylor
- **Contact**: 555-1013
- **Address**: 159 Chestnut Court, Springfield, Illinois 62713

## Database Records Created

For each student, the following records were created:
1. **Contact** - Personal information, parent details, address
2. **Registration** - Course registration with approval
3. **Admission** - Admission confirmation
4. **Student** - Student record with batch assignment and roll number

## Re-running the Seeder

To recreate or update the demo data:

```bash
php artisan db:seed --class=StudentDemoDataSeeder
```

The seeder is smart and will:
- ✅ Skip existing records (won't create duplicates)
- ✅ Create missing divisions, courses, and batches
- ✅ Add new students if they don't exist
- ✅ Complete partial student records

## Database Statistics

- **14 Student Contacts** with complete personal information
- **14 Registrations** (all approved)
- **14 Admissions** with unique admission numbers
- **14 Student Records** with batch and roll number assignments
- **6 Courses** (Grade 1-6)
- **12 Batches** (2 sections per grade)
- **1 Division** (Primary Section)

## Features Included

### Personal Information
- ✅ First name, last name
- ✅ Gender (male/female)
- ✅ Birth dates (age-appropriate for each grade)
- ✅ Blood groups (varied: A+, A-, B+, B-, O+, O-, AB+, AB-)
- ✅ Contact numbers
- ✅ Email addresses

### Family Information
- ✅ Father's name
- ✅ Mother's name
- ✅ Parent contact numbers

### Address Information
- ✅ Complete street addresses
- ✅ City: Springfield
- ✅ State: Illinois
- ✅ ZIP codes (unique for each student)
- ✅ Country: USA

### Academic Information
- ✅ Course/Grade assignment
- ✅ Batch/Section assignment
- ✅ Roll numbers (sequential within batches)
- ✅ Admission numbers (year-based)
- ✅ Registration records (approved)
- ✅ Admission dates

## Use Cases

This demo data is perfect for:
1. **Testing** - Student listing, searching, filtering
2. **Reports** - Class lists, attendance sheets, grade cards
3. **Training** - Staff familiarization with the system
4. **Development** - Testing new features with realistic data
5. **Demos** - Showing the system to potential clients
6. **Excel Import Testing** - Use as reference for import formats

## Next Steps

You can now:
1. View students in the admin panel
2. Generate class lists and reports
3. Practice attendance marking
4. Test exam and grade entry
5. Try fee management
6. Test bulk operations
7. Use as templates for Excel import

---

**Created**: February 24, 2026  
**Seeder**: `database/seeders/StudentDemoDataSeeder.php`
