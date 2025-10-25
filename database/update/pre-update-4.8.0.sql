--
-- Database: `InstiKit`
--

-- --------------------------------------------------------

--
-- InstiKit 4.8.0 post update queries
--

START TRANSACTION;

SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `site_blocks` ADD `menu_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `sub_title`, ADD INDEX `menu_id` (`menu_id`);
ALTER TABLE `site_blocks` ADD CONSTRAINT `site_blocks_menu_id_foreign` FOREIGN KEY (`menu_id`) REFERENCES `site_menus`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `custom_fields` CHANGE `type` `type` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;

SET FOREIGN_KEY_CHECKS = 1;

COMMIT;