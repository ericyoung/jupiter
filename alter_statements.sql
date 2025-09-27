-- ALTER statements to update the database schema

-- Add locked field to orders_av table
ALTER TABLE `orders_av` ADD COLUMN `locked` TINYINT(1) DEFAULT 0 AFTER `updated_at`;

-- Add new fields to users table
ALTER TABLE `users` ADD COLUMN `phone` VARCHAR(20) NULL AFTER `email`;
ALTER TABLE `users` ADD COLUMN `phone_ext` VARCHAR(10) NULL AFTER `phone`;
ALTER TABLE `users` ADD COLUMN `alt_phone` VARCHAR(20) NULL AFTER `phone_ext`;
ALTER TABLE `users` ADD COLUMN `alt_phone_ext` VARCHAR(10) NULL AFTER `alt_phone`;
ALTER TABLE `users` ADD COLUMN `account_rep_id` INT NULL AFTER `alt_phone_ext`;
ALTER TABLE `users` ADD COLUMN `is_active` TINYINT(1) DEFAULT 1 AFTER `account_rep_id`;

-- Add active field to tours table (if not already present)
ALTER TABLE `tours` ADD COLUMN `active` TINYINT(1) DEFAULT 1 AFTER `produced_by`;

-- Add required fields to orders table for venue and city autocomplete
ALTER TABLE `orders` ADD COLUMN `event_venue` VARCHAR(255) NULL AFTER `updated_at`;
ALTER TABLE `orders` ADD COLUMN `event_city` VARCHAR(100) NULL AFTER `event_venue`;