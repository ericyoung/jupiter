-- Create audits table to track user actions in the application
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

-- Insert some sample audit records
INSERT INTO `audits` (`user_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 'user_login', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (example)', '2025-09-25 10:00:00'),
(1, 'user_update', 'Updated user profile information', '127.0.0.1', 'Mozilla/5.0 (example)', '2025-09-25 10:05:00'),
(2, 'role_create', 'Created new role: moderator', '127.0.0.1', 'Mozilla/5.0 (example)', '2025-09-25 10:10:00');