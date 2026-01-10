# Fix "Could not find selected school" Error

## Problem
When trying to login as admin, you receive the error message: **"Could not find selected school"**

## Cause
This error occurs when:
1. The admin user doesn't have a school/team assigned in the database
2. The admin user's `current_team_id` is not set or is invalid
3. The `model_has_roles` table doesn't have a `team_id` associated with the admin role

## Solutions

You have **3 options** to fix this issue:

---

### **Option 1: Run the Artisan Command (Recommended)**

This is the easiest and safest method.

1. Open your terminal/command prompt
2. Navigate to the project directory:
   ```bash
   cd "/Applications/MAMP/htdocs/shulesoft/school-ms"
   ```
3. Run the fix command:
   ```bash
   php artisan admin:fix-school
   ```
4. Try logging in again

---

### **Option 2: Run the PHP Script**

If the artisan command doesn't work, use this standalone PHP script.

1. Open your terminal/command prompt
2. Navigate to the project directory:
   ```bash
   cd "/Applications/MAMP/htdocs/InstiKit School v5.0.0 Nulled/schoolms"
   ```
3. Run the fix script:
   ```bash
   php fix_admin_school.php
   ```
4. Try logging in again

---

### **Option 3: Run SQL Queries Manually**

If both above methods fail, you can manually fix the database using SQL.

1. Open phpMyAdmin (or your MySQL client)
2. Select your school management database
3. Open the file `fix_admin_school.sql` and run the queries step by step
4. Follow the comments in the SQL file carefully
5. Try logging in again

**Important SQL Steps:**

1. **Check if a team/school exists:**
   ```sql
   SELECT * FROM teams;
   ```

2. **If no team exists, create one:**
   ```sql
   INSERT INTO teams (name, alias, meta, created_at, updated_at)
   VALUES ('Default School', 'default-school', '{}', NOW(), NOW());
   ```

3. **Get the team ID:**
   ```sql
   SET @team_id = (SELECT id FROM teams LIMIT 1);
   ```

4. **Update admin role assignments:**
   ```sql
   UPDATE model_has_roles 
   SET team_id = @team_id 
   WHERE model_type = 'User' 
   AND role_id IN (SELECT id FROM roles WHERE name = 'admin');
   ```

5. **Update admin user metadata:**
   ```sql
   UPDATE users 
   SET meta = JSON_SET(COALESCE(meta, '{}'), '$.current_team_id', @team_id)
   WHERE id IN (
       SELECT DISTINCT u.id 
       FROM users u
       INNER JOIN model_has_roles mhr ON mhr.model_id = u.id 
       INNER JOIN roles r ON r.id = mhr.role_id 
       WHERE r.name = 'admin' AND mhr.model_type = 'User'
   );
   ```

6. **Verify the changes:**
   ```sql
   SELECT u.id, u.name, u.email, u.meta, mhr.team_id 
   FROM users u
   INNER JOIN model_has_roles mhr ON mhr.model_id = u.id 
   INNER JOIN roles r ON r.id = mhr.role_id 
   WHERE r.name = 'admin' AND mhr.model_type = 'User';
   ```

---

## What These Fixes Do

All three methods perform the same operations:

1. ✅ Check if a school/team exists in the database
2. ✅ Create a default school if none exists
3. ✅ Assign the admin user(s) to that school in the `model_has_roles` table
4. ✅ Set the `current_team_id` in the admin user's metadata

---

## After Running the Fix

1. Clear your browser cache and cookies
2. Try logging in as admin again
3. You should now be able to access the system

---

## Prevention

To prevent this issue in the future:

1. Always ensure at least one school/team exists in the system
2. When creating admin users, make sure they're assigned to a team
3. After installation, run the setup wizard to create the initial school

---

## Still Having Issues?

If you still can't login after trying all methods:

1. Check the Laravel logs: `storage/logs/laravel.log`
2. Check your database connection in `.env`
3. Verify that the database has been migrated: `php artisan migrate:status`
4. Make sure the database seeding was completed
5. Check if the admin user exists: Run this SQL query:
   ```sql
   SELECT u.*, r.name as role_name 
   FROM users u 
   LEFT JOIN model_has_roles mhr ON u.id = mhr.model_id 
   LEFT JOIN roles r ON mhr.role_id = r.id 
   WHERE r.name = 'admin';
   ```

If no admin user exists, you need to create one first through the installation process.
