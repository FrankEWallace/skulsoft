--
-- Database: `InstiKit`
--

-- --------------------------------------------------------

--
-- InstiKit 4.9.0 post update queries
--

START TRANSACTION;

SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `exam_results` ADD `is_cumulative` BOOLEAN NOT NULL DEFAULT FALSE AFTER `attempt`;
ALTER TABLE `contacts` ADD `unique_id_number4` VARCHAR(50) NULL DEFAULT NULL AFTER `unique_id_number3`, ADD `unique_id_number5` VARCHAR(50) NULL DEFAULT NULL AFTER `unique_id_number4`;

ALTER TABLE `student_fee_payments` ADD `concession_amount` DECIMAL(25,5) NOT NULL DEFAULT '0' AFTER `amount`;

SET FOREIGN_KEY_CHECKS = 1;

COMMIT;