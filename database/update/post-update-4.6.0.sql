--
-- Database: `InstiKit`
--

-- --------------------------------------------------------

--
-- InstiKit 4.6.0 post update queries
--

START TRANSACTION;

SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `employees` ADD `team_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `code_number`, ADD INDEX `employees` (`team_id`);
ALTER TABLE `employees` ADD CONSTRAINT `employees_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

UPDATE employees JOIN contacts ON employees.contact_id = contacts.id SET employees.team_id = contacts.team_id;

SET FOREIGN_KEY_CHECKS = 1;

COMMIT;