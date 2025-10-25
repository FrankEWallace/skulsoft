--
-- Database: `InstiKit`
--

-- --------------------------------------------------------

--
-- InstiKit 4.5.0 pre update queries
--

START TRANSACTION;

SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `online_exams` ADD `end_date` DATE NULL DEFAULT NULL AFTER `start_time`;

SET FOREIGN_KEY_CHECKS = 1;

COMMIT;