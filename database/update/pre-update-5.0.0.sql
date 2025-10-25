--
-- Database: `InstiKit`
--

-- --------------------------------------------------------

--
-- InstiKit 5.0.0 post update queries
--

START TRANSACTION;

SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `contacts` ADD `locality` VARCHAR(50) NULL DEFAULT NULL AFTER `blood_group`;
ALTER TABLE `enquiries` ADD `nature` VARCHAR(50) NULL DEFAULT NULL AFTER `code_number`;
ALTER TABLE `enquiries` ADD `description` TEXT NULL DEFAULT NULL AFTER `status`;
ALTER TABLE `students` ADD `enrollment_status_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `enrollment_type_id`, ADD INDEX `enrollment_status_id` (`enrollment_status_id`);
ALTER TABLE `students` ADD CONSTRAINT `students_enrollment_status_id_foreign` FOREIGN KEY (`enrollment_status_id`) REFERENCES `options`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `registrations` ADD `stage_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `course_id`, ADD INDEX `stage_id` (`stage_id`);
ALTER TABLE `registrations` ADD CONSTRAINT `registrations_stage_id_foreign` FOREIGN KEY (`stage_id`) REFERENCES `options`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;

ALTER TABLE `registrations` ADD `enrollment_type_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `course_id`, ADD INDEX `enrollment_type_id` (`enrollment_type_id`);
ALTER TABLE `registrations` ADD CONSTRAINT `registrations_enrollment_type_id_foreign` FOREIGN KEY (`enrollment_type_id`) REFERENCES `options`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;

ALTER TABLE `enquiry_follow_ups` ADD `stage_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `enquiry_id`, ADD INDEX `stage_id` (`stage_id`);
ALTER TABLE `enquiry_follow_ups` ADD CONSTRAINT `enquiry_follow_ups_stage_id_foreign` FOREIGN KEY (`stage_id`) REFERENCES `options`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;

ALTER TABLE `transactions` ADD `category_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `period_id`, ADD INDEX `category_id` (`category_id`);
ALTER TABLE `transactions` ADD CONSTRAINT `transactions_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `options`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `transactions` ADD `description` TEXT NULL DEFAULT NULL AFTER `handling_fee`;

ALTER TABLE `admissions` ADD `is_provisional` BOOLEAN NOT NULL DEFAULT FALSE AFTER `code_number`;
ALTER TABLE `admissions` ADD `provisional_number_format` VARCHAR(50) NULL DEFAULT NULL AFTER `is_provisional`, ADD `provisional_number` INT NULL DEFAULT NULL AFTER `provisional_number_format`, ADD `provisional_code_number` VARCHAR(50) NULL DEFAULT NULL AFTER `provisional_number`;

ALTER TABLE `teams` ADD `code` VARCHAR(50) NULL DEFAULT NULL AFTER `name`;

ALTER TABLE `books` ADD `category_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `language_id`, ADD INDEX `category_id` (`category_id`);
ALTER TABLE `books` ADD CONSTRAINT `books_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `options`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;

ALTER TABLE `book_copies` ADD `vendor` VARCHAR(100) NULL DEFAULT NULL AFTER `suffix`;
ALTER TABLE `book_copies` ADD `invoice_number` VARCHAR(100) NULL DEFAULT NULL AFTER `vendor`;
ALTER TABLE `book_copies` ADD `invoice_date` DATE NULL DEFAULT NULL AFTER `invoice_number`;
ALTER TABLE `book_copies` ADD `room_number` VARCHAR(100) NULL DEFAULT NULL AFTER `invoice_date`;
ALTER TABLE `book_copies` ADD `rack_number` INT(10) NULL DEFAULT NULL AFTER `room_number`;
ALTER TABLE `book_copies` ADD `shelf_number` INT(10) NULL DEFAULT NULL AFTER `rack_number`;
ALTER TABLE `book_copies` CHANGE `prefix` `number_format` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL, CHANGE `suffix` `code_number` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;

ALTER TABLE `book_transactions` ADD `number_format` VARCHAR(50) NULL DEFAULT NULL AFTER `team_id`, ADD `number` INT NULL DEFAULT NULL AFTER `number_format`, ADD `code_number` VARCHAR(50) NULL DEFAULT NULL AFTER `number`;

ALTER TABLE `book_transaction_records` ADD `condition_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `return_date`, ADD `return_status` VARCHAR(50) NULL DEFAULT NULL AFTER `condition_id`, ADD `charges` JSON NULL DEFAULT NULL AFTER `return_status`, ADD INDEX `condition_id` (`condition_id`);

ALTER TABLE `book_copies` ADD `hold_status` VARCHAR(50) NULL DEFAULT NULL AFTER `shelf_number`;

SET FOREIGN_KEY_CHECKS = 1;

COMMIT;