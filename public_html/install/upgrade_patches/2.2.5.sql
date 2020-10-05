ALTER TABLE `lc_products`
ADD COLUMN `recommended_price` DECIMAL(11,4) NOT NULL AFTER `purchase_price_currency_code`,
CHANGE COLUMN `weight` `weight` DECIMAL(11,4) NOT NULL AFTER `quantity_unit_id`,
CHANGE COLUMN `dim_x` `dim_x` DECIMAL(11,4) NOT NULL AFTER `weight_class`,
CHANGE COLUMN `dim_y` `dim_y` DECIMAL(11,4) NOT NULL AFTER `dim_x`,
CHANGE COLUMN `dim_z` `dim_z` DECIMAL(11,4) NOT NULL AFTER `dim_y`,
CHANGE COLUMN `purchase_price` `purchase_price` DECIMAL(11,4) NOT NULL AFTER `dim_class`;
