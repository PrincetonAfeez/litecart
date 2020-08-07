CREATE TABLE `lc_newsletter_recipients` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`email` VARCHAR(128) NOT NULL,
	`date_created` DATETIME NOT NULL,
	PRIMARY KEY (`id`),
  UNIQUE INDEX `email` (`email`)
);
-- --------------------------------------------------------
INSERT INTO `lc_newsletter_recipients`
(email, date_created)
SELECT email, date_created FROM `lc_customers`
WHERE status AND newsletter;
-- --------------------------------------------------------
ALTER TABLE `lc_customers`
DROP COLUMN `newsletter`,
CHANGE COLUMN `last_ip` `last_ip_address` VARCHAR(39) NOT NULL AFTER `num_logins`,
CHANGE COLUMN `last_host` `last_hostname` VARCHAR(64) NOT NULL AFTER `last_ip_address`,
CHANGE COLUMN `last_agent` `last_user_agent` VARCHAR(256) NOT NULL AFTER `last_hostname`;
-- --------------------------------------------------------
UPDATE `lc_settings` SET `value` = '0' WHERE `key` = 'cache_clear_thumbnails' LIMIT 1;
-- --------------------------------------------------------
UPDATE `lc_settings` SET `key` = 'store_template' WHERE `key` = 'store_template_catalog' LIMIT 1;
-- --------------------------------------------------------
UPDATE `lc_settings` SET `value` = REGEXP_REPLACE(`value`, '\.catalog$', '') WHERE `key` = 'store_template' LIMIT 1;
-- --------------------------------------------------------
DELETE FROM `lc_settings` WHERE `key` = 'store_template_admin' LIMIT 1;
-- --------------------------------------------------------
DELETE FROM `lc_settings` WHERE `key` = 'gzip_enabled' LIMIT 1;
-- --------------------------------------------------------
RENAME TABLE `lc_products_options_stock` TO `lc_products_stock`;
-- --------------------------------------------------------
INSERT INTO `lc_products_stock`
(product_id, sku, weight, weight_class, dim_x, dim_y, dim_z, dim_class, quantity)
SELECT id, sku, weight, weight_class, dim_x, dim_y, dim_z, dim_class, quantity FROM `lc_products`
WHERE id NOT IN (
  SELECT DISTINCT product_id from `lc_products_stock`
);
-- --------------------------------------------------------
ALTER TABLE `lc_products_stock`
DROP COLUMN `date_updated`,
DROP COLUMN `date_created`,
DROP INDEX `product_option_stock`,
ADD UNIQUE INDEX `stock_option` (`product_id`, `combination`);
-- --------------------------------------------------------
ALTER TABLE `lc_orders_items`
ADD COLUMN `stock_option_id` INT(11) NULL DEFAULT NULL AFTER `product_id`,
ADD INDEX `product_id` (`product_id`),
ADD INDEX `stock_option_id` (`stock_option_id`);
-- --------------------------------------------------------
UPDATE `lc_orders_items` oi
LEFT JOIN `lc_products_stock_options` pso ON (pso.product_id = oi.product_id AND pso.combination = oi.option_stock_combination)
SET stock_option_id = pso.id;
-- --------------------------------------------------------
ALTER TABLE `lc_orders_items`
DROP COLUMN `option_stock_combination`;
-- --------------------------------------------------------
RENAME TABLE `lc_manufacturers` TO `lc_brands`;
-- --------------------------------------------------------
RENAME TABLE `lc_manufacturers_info` TO `lc_brands_info`;
-- --------------------------------------------------------
ALTER TABLE `lc_brands_info`
CHANGE COLUMN `manufacturer_id` `brand_id` INT(11) NOT NULL AFTER `id`,
ADD UNIQUE INDEX `brand_info` (`brand_id`, `language_code`),
ADD INDEX `brand_id` (`brand_id`);
-- --------------------------------------------------------
ALTER TABLE `lc_products`
CHANGE COLUMN `manufacturer_id` `brand_id` INT(11) NOT NULL AFTER `status`,
DROP INDEX `manufacturer_id`,
ADD INDEX `brand_id` (`brand_id`);