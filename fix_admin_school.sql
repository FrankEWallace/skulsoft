-- Fix Admin School Assignment
-- Run this SQL in your database (via phpMyAdmin or MySQL client)

-- Step 1: Check if there are any teams/schools
SELECT * FROM teams;

-- Step 2: If no teams exist, create a default one
-- (Only run this if the above SELECT returns no results)
INSERT INTO teams (name, alias, meta, created_at, updated_at)
VALUES ('Default School', 'default-school', '{}', NOW(), NOW());

-- Step 3: Get the team ID (note the id from the team created above or existing team)
SET @team_id = (SELECT id FROM teams LIMIT 1);

-- Step 4: Update all admin user role assignments to include the team
UPDATE model_has_roles 
SET team_id = @team_id 
WHERE model_type = 'User' 
AND role_id IN (SELECT id FROM roles WHERE name = 'admin');

-- Step 5: Update admin users' meta to set current_team_id
-- First, find admin users
SELECT u.id, u.name, u.email, u.meta 
FROM users u
INNER JOIN model_has_roles mhr ON mhr.model_id = u.id 
INNER JOIN roles r ON r.id = mhr.role_id 
WHERE r.name = 'admin' AND mhr.model_type = 'User';

-- Step 6: Update each admin user's meta (replace USER_ID with actual user ID from above query)
-- You need to modify the meta JSON to add/update current_team_id
-- For example, if user ID is 1 and team_id is 1:
UPDATE users 
SET meta = JSON_SET(COALESCE(meta, '{}'), '$.current_team_id', @team_id)
WHERE id IN (
    SELECT DISTINCT u.id 
    FROM users u
    INNER JOIN model_has_roles mhr ON mhr.model_id = u.id 
    INNER JOIN roles r ON r.id = mhr.role_id 
    WHERE r.name = 'admin' AND mhr.model_type = 'User'
);

-- Step 7: Verify the changes
SELECT u.id, u.name, u.email, u.meta, mhr.team_id 
FROM users u
INNER JOIN model_has_roles mhr ON mhr.model_id = u.id 
INNER JOIN roles r ON r.id = mhr.role_id 
WHERE r.name = 'admin' AND mhr.model_type = 'User';

-- You should see:
-- 1. team_id column populated in model_has_roles
-- 2. meta column in users should contain: {"current_team_id": <team_id>}
