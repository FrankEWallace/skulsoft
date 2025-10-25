--
-- Database: `InstiKit`
--

-- --------------------------------------------------------

--
-- InstiKit 4.7.0 post update queries
--

START TRANSACTION;

SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `timetable_allocations` ADD `employee_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `subject_id`, ADD INDEX `employee_id` (`employee_id`);
ALTER TABLE `timetable_allocations` ADD CONSTRAINT `timetable_allocations_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;

SET FOREIGN_KEY_CHECKS = 1;

COMMIT;