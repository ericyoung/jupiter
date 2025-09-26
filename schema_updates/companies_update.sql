-- Append updated companies table structure with additional fields

-- Drop existing companies table if it exists (for fresh installation)
DROP TABLE IF EXISTS `companies`;

-- Create updated companies table with additional fields
CREATE TABLE `companies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `company_number` varchar(50) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `address1` varchar(255) DEFAULT NULL,
  `address2` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state_or_province` varchar(100) DEFAULT NULL,
  `zip` varchar(20) DEFAULT NULL,
  `primary_phone` varchar(20) NOT NULL,
  `contact_name` varchar(100) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_name` (`company_name`),
  UNIQUE KEY `company_number` (`company_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Add company_id column to users table to associate users with companies
ALTER TABLE `users` ADD COLUMN `company_id` int NULL;

-- Add foreign key constraint for company_id in users table
ALTER TABLE `users` ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Add index for company_id in users table
ALTER TABLE `users` ADD KEY `company_id` (`company_id`);