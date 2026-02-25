-- ============================================
-- Demo Users SQL Dump for SkulSoft School MS
-- ============================================
-- Created: February 23, 2026
-- Purpose: Create demo users for all roles
-- Default Password: password123
-- Password Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- ============================================

-- Note: This assumes team_id = 1 exists
-- Run after basic seeders (RoleSeeder, TeamSeeder, etc.)

-- ============================================
-- DEMO USERS
-- ============================================

-- 1. Admin User
INSERT INTO `users` (`name`, `email`, `username`, `password`, `email_verified_at`, `status`, `created_at`, `updated_at`)
VALUES 
('Admin User', 'admin@demo.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), 'activated', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- 2. Manager Demo
INSERT INTO `users` (`name`, `email`, `username`, `password`, `email_verified_at`, `status`, `created_at`, `updated_at`)
VALUES 
('Manager Demo', 'manager@demo.com', 'manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), 'activated', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- 3. Principal Demo
INSERT INTO `users` (`name`, `email`, `username`, `password`, `email_verified_at`, `status`, `created_at`, `updated_at`)
VALUES 
('Principal Demo', 'principal@demo.com', 'principal', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), 'activated', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- 4. Staff Member
INSERT INTO `users` (`name`, `email`, `username`, `password`, `email_verified_at`, `status`, `created_at`, `updated_at`)
VALUES 
('Staff Member', 'staff@demo.com', 'staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), 'activated', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- 5. John Accountant
INSERT INTO `users` (`name`, `email`, `username`, `password`, `email_verified_at`, `status`, `created_at`, `updated_at`)
VALUES 
('John Accountant', 'accountant@demo.com', 'accountant', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), 'activated', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- 6. Sarah Librarian
INSERT INTO `users` (`name`, `email`, `username`, `password`, `email_verified_at`, `status`, `created_at`, `updated_at`)
VALUES 
('Sarah Librarian', 'librarian@demo.com', 'librarian', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), 'activated', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- 7. Mike Exam Coordinator
INSERT INTO `users` (`name`, `email`, `username`, `password`, `email_verified_at`, `status`, `created_at`, `updated_at`)
VALUES 
('Mike Exam Coordinator', 'exam@demo.com', 'exam-coordinator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), 'activated', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- 8. David Transport Manager
INSERT INTO `users` (`name`, `email`, `username`, `password`, `email_verified_at`, `status`, `created_at`, `updated_at`)
VALUES 
('David Transport Manager', 'transport@demo.com', 'transport', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), 'activated', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- 9. Lisa Inventory Manager
INSERT INTO `users` (`name`, `email`, `username`, `password`, `email_verified_at`, `status`, `created_at`, `updated_at`)
VALUES 
('Lisa Inventory Manager', 'inventory@demo.com', 'inventory', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), 'activated', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- 10. Chef Mess Manager
INSERT INTO `users` (`name`, `email`, `username`, `password`, `email_verified_at`, `status`, `created_at`, `updated_at`)
VALUES 
('Chef Mess Manager', 'mess@demo.com', 'mess-manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), 'activated', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- 11. Robert Hostel Warden
INSERT INTO `users` (`name`, `email`, `username`, `password`, `email_verified_at`, `status`, `created_at`, `updated_at`)
VALUES 
('Robert Hostel Warden', 'hostel@demo.com', 'hostel-warden', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), 'activated', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- 12. Mary Attendance Officer
INSERT INTO `users` (`name`, `email`, `username`, `password`, `email_verified_at`, `status`, `created_at`, `updated_at`)
VALUES 
('Mary Attendance Officer', 'attendance@demo.com', 'attendance', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), 'activated', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- 13. Emma Receptionist
INSERT INTO `users` (`name`, `email`, `username`, `password`, `email_verified_at`, `status`, `created_at`, `updated_at`)
VALUES 
('Emma Receptionist', 'reception@demo.com', 'receptionist', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), 'activated', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- 14. Tom Student
INSERT INTO `users` (`name`, `email`, `username`, `password`, `email_verified_at`, `status`, `created_at`, `updated_at`)
VALUES 
('Tom Student', 'student@demo.com', 'student', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), 'activated', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- 15. Parent Guardian
INSERT INTO `users` (`name`, `email`, `username`, `password`, `email_verified_at`, `status`, `created_at`, `updated_at`)
VALUES 
('Parent Guardian', 'parent@demo.com', 'parent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), 'activated', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- 16. Basic User
INSERT INTO `users` (`name`, `email`, `username`, `password`, `email_verified_at`, `status`, `created_at`, `updated_at`)
VALUES 
('Basic User', 'user@demo.com', 'basicuser', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), 'activated', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- ============================================
-- ASSIGN ROLES TO USERS
-- ============================================
-- Note: Run these after the users are created
-- The user IDs will be auto-incremented, adjust if needed

-- Assign roles (assuming sequential IDs)
-- Adjust the user_id values based on your actual user IDs

-- Admin role (role_id = 1)
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`, `team_id`)
SELECT 1, 'App\\Models\\User', id, 1 
FROM `users` WHERE `email` = 'admin@demo.com'
ON DUPLICATE KEY UPDATE `role_id` = 1;

-- Manager role (role_id = 2)
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`, `team_id`)
SELECT 2, 'App\\Models\\User', id, 1 
FROM `users` WHERE `email` = 'manager@demo.com'
ON DUPLICATE KEY UPDATE `role_id` = 2;

-- Principal role (role_id = 3)
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`, `team_id`)
SELECT 3, 'App\\Models\\User', id, 1 
FROM `users` WHERE `email` = 'principal@demo.com'
ON DUPLICATE KEY UPDATE `role_id` = 3;

-- Staff role (role_id = 4)
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`, `team_id`)
SELECT 4, 'App\\Models\\User', id, 1 
FROM `users` WHERE `email` = 'staff@demo.com'
ON DUPLICATE KEY UPDATE `role_id` = 4;

-- Accountant role (role_id = 5)
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`, `team_id`)
SELECT 5, 'App\\Models\\User', id, 1 
FROM `users` WHERE `email` = 'accountant@demo.com'
ON DUPLICATE KEY UPDATE `role_id` = 5;

-- Librarian role (role_id = 6)
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`, `team_id`)
SELECT 6, 'App\\Models\\User', id, 1 
FROM `users` WHERE `email` = 'librarian@demo.com'
ON DUPLICATE KEY UPDATE `role_id` = 6;

-- Exam Incharge role (role_id = 7)
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`, `team_id`)
SELECT 7, 'App\\Models\\User', id, 1 
FROM `users` WHERE `email` = 'exam@demo.com'
ON DUPLICATE KEY UPDATE `role_id` = 7;

-- Transport Incharge role (role_id = 8)
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`, `team_id`)
SELECT 8, 'App\\Models\\User', id, 1 
FROM `users` WHERE `email` = 'transport@demo.com'
ON DUPLICATE KEY UPDATE `role_id` = 8;

-- Inventory Incharge role (role_id = 9)
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`, `team_id`)
SELECT 9, 'App\\Models\\User', id, 1 
FROM `users` WHERE `email` = 'inventory@demo.com'
ON DUPLICATE KEY UPDATE `role_id` = 9;

-- Mess Incharge role (role_id = 10)
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`, `team_id`)
SELECT 10, 'App\\Models\\User', id, 1 
FROM `users` WHERE `email` = 'mess@demo.com'
ON DUPLICATE KEY UPDATE `role_id` = 10;

-- Hostel Incharge role (role_id = 11)
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`, `team_id`)
SELECT 11, 'App\\Models\\User', id, 1 
FROM `users` WHERE `email` = 'hostel@demo.com'
ON DUPLICATE KEY UPDATE `role_id` = 11;

-- Attendance Assistant role (role_id = 12)
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`, `team_id`)
SELECT 12, 'App\\Models\\User', id, 1 
FROM `users` WHERE `email` = 'attendance@demo.com'
ON DUPLICATE KEY UPDATE `role_id` = 12;

-- Receptionist role (role_id = 13)
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`, `team_id`)
SELECT 13, 'App\\Models\\User', id, 1 
FROM `users` WHERE `email` = 'reception@demo.com'
ON DUPLICATE KEY UPDATE `role_id` = 13;

-- Student role (role_id = 14)
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`, `team_id`)
SELECT 14, 'App\\Models\\User', id, 1 
FROM `users` WHERE `email` = 'student@demo.com'
ON DUPLICATE KEY UPDATE `role_id` = 14;

-- Guardian role (role_id = 15)
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`, `team_id`)
SELECT 15, 'App\\Models\\User', id, 1 
FROM `users` WHERE `email` = 'parent@demo.com'
ON DUPLICATE KEY UPDATE `role_id` = 15;

-- User role (role_id = 16)
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`, `team_id`)
SELECT 16, 'App\\Models\\User', id, 1 
FROM `users` WHERE `email` = 'user@demo.com'
ON DUPLICATE KEY UPDATE `role_id` = 16;

-- ============================================
-- ASSIGN USERS TO TEAM
-- ============================================

-- Assign all demo users to team 1
INSERT INTO `team_user` (`team_id`, `user_id`, `name`, `start_date`, `created_at`, `updated_at`)
SELECT 1, id, 'Demo School', NOW(), NOW(), NOW()
FROM `users` 
WHERE `email` IN (
    'admin@demo.com', 'manager@demo.com', 'principal@demo.com', 'staff@demo.com',
    'accountant@demo.com', 'librarian@demo.com', 'exam@demo.com', 'transport@demo.com',
    'inventory@demo.com', 'mess@demo.com', 'hostel@demo.com', 'attendance@demo.com',
    'reception@demo.com', 'student@demo.com', 'parent@demo.com', 'user@demo.com'
)
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- ============================================
-- UPDATE USER META (Current Team)
-- ============================================

-- Set current_team_id in user meta
UPDATE `users` 
SET `meta` = JSON_SET(COALESCE(`meta`, '{}'), '$.current_team_id', 1)
WHERE `email` IN (
    'admin@demo.com', 'manager@demo.com', 'principal@demo.com', 'staff@demo.com',
    'accountant@demo.com', 'librarian@demo.com', 'exam@demo.com', 'transport@demo.com',
    'inventory@demo.com', 'mess@demo.com', 'hostel@demo.com', 'attendance@demo.com',
    'reception@demo.com', 'student@demo.com', 'parent@demo.com', 'user@demo.com'
);

-- ============================================
-- VERIFICATION QUERIES
-- ============================================

-- Check created users
-- SELECT id, name, email, username FROM users WHERE email LIKE '%@demo.com%';

-- Check role assignments
-- SELECT u.name, u.email, r.name as role 
-- FROM users u 
-- JOIN model_has_roles mhr ON u.id = mhr.model_id 
-- JOIN roles r ON mhr.role_id = r.id 
-- WHERE u.email LIKE '%@demo.com%';

-- ============================================
-- END OF DUMP
-- ============================================
