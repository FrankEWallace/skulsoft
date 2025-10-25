--
-- Database: `InstiKit`
--

-- --------------------------------------------------------

--
-- InstiKit 4.2.0 pre update queries
--

START TRANSACTION;

SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `templates`
MODIFY COLUMN enabled_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `custom_fields` ADD `team_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `uuid`, ADD INDEX `team_id` (`team_id`);
ALTER TABLE `custom_fields` ADD CONSTRAINT `custom_fields_team_id` FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

SET FOREIGN_KEY_CHECKS = 1;

COMMIT;