--
-- Database: `InstiKit`
--

-- --------------------------------------------------------

--
-- InstiKit 4.1.0 pre update queries
--

START TRANSACTION;

SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `pay_heads` ADD `position` INT NOT NULL DEFAULT '0' AFTER `category`;
ALTER TABLE `salary_structures` ADD `net_employee_contribution` DECIMAL(25,5) NOT NULL DEFAULT '0' AFTER `net_deduction`, ADD `net_employer_contribution` DECIMAL(25,5) NOT NULL DEFAULT '0' AFTER `net_employee_contribution`;

ALTER TABLE `enquiries` ADD `employee_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `source_id`, ADD INDEX `employee_id` (`employee_id`);
ALTER TABLE `enquiries` ADD CONSTRAINT `enquiries_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `complaints` CHANGE `resolution_date` `resolved_at` DATETIME NULL DEFAULT NULL;

ALTER TABLE `timetable_records` ADD `room_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `class_timing_id`, ADD INDEX `room_id` (`room_id`);
ALTER TABLE `timetable_records` ADD CONSTRAINT `timetable_records_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;

SET FOREIGN_KEY_CHECKS = 1;

COMMIT;