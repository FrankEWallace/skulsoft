--
-- Database: `InstiKit`
--

-- --------------------------------------------------------

--
-- InstiKit 4.10.0 post update queries
--

START TRANSACTION;

SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `payrolls` CHANGE `status` `payment_status` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `payrolls` ADD `status` VARCHAR(50) NULL DEFAULT NULL AFTER `paid`;
update payrolls set status = 'processed';

ALTER TABLE `leave_requests` ADD `is_half_day` BOOLEAN NOT NULL DEFAULT FALSE AFTER `request_user_id`;

SET FOREIGN_KEY_CHECKS = 1;

COMMIT;