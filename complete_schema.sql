-- Jupiter PHP Project - Complete Database Schema
-- Includes all tables and initial data

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `byp`;
USE `byp`;

-- Set SQL mode
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Table structure for roles with auto-increment starting at 10
CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `is_client_role` tinyint(1) NOT NULL DEFAULT '0',
  `hierarchy_level` int NOT NULL DEFAULT '0',
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table structure for companies
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

-- Table structure for users with auto-increment starting at 5
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int NOT NULL,
  `company_id` int NULL,
  `is_active` tinyint(1) DEFAULT '0',
  `activation_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `users_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Set auto-increment values
ALTER TABLE `companies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

-- Insert default roles with hierarchy
INSERT INTO `roles` (`id`, `name`, `display_name`, `is_client_role`, `hierarchy_level`, `description`) VALUES
(1, 'superadmin', 'Super Administrator', 0, 100, 'Super Administrator with highest privileges'),
(2, 'executive', 'Executive', 0, 90, 'Executive role with high privileges'),
(3, 'accounts', 'Accounts', 0, 80, 'Accounts role for financial management'),
(4, 'audio', 'Audio', 0, 50, 'Audio department role'),
(5, 'video', 'Video', 0, 50, 'Video department role'),
(6, 'graphics', 'Graphics', 0, 50, 'Graphics department role'),
(7, 'dubbing', 'Dubbing', 0, 40, 'Dubbing department role'),
(8, 'client_admin', 'Client Administrator', 1, 10, 'Client Administrator with elevated client privileges'),
(9, 'client', 'Client', 1, 5, 'Basic client role');

-- Insert default companies
INSERT INTO `companies` (`id`, `company_name`, `company_number`, `enabled`, `primary_phone`) VALUES
(1, 'Demo Company', 'DC001', 1, '555-123-4567');

-- Insert default users
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role_id`, `company_id`, `is_active`, `activation_token`, `created_at`, `updated_at`) VALUES
(1, 'Eric Young', 'youngeric@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NULL, 1, NULL, '2025-09-10 23:16:52', '2025-09-10 23:16:52'),
(2, 'Barry Leff', 'barry@beaver.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 9, 1, 1, NULL, '2025-09-10 23:16:52', '2025-09-10 23:16:52'),
(3, 'Super Admin', 'superadmin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NULL, 1, NULL, '2025-09-10 23:18:05', '2025-09-10 23:18:05'),
(4, 'Client User', 'client@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 9, 1, 1, NULL, '2025-09-10 23:18:05', '2025-09-10 23:18:05');

-- Table structure for audits with auto-increment starting at 1
CREATE TABLE `audits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `action` varchar(255) NOT NULL,
  `description` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `audits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

COMMIT;
