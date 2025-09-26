INSERT INTO `companies` (`id`, `company_name`, `company_number`, `enabled`, `primary_phone`) VALUES
(1, 'Demo Company', 'DC001', 1, '555-123-4567');

-- Insert users
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role_id`, `is_active`, `activation_token`, `created_at`, `updated_at`) VALUES
(1, 'Eric Young', 'youngeric@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, NULL, '2025-09-10 23:16:52', '2025-09-10 23:16:52'),
(2, 'Barry Leff', 'barry@beaver.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 9, 1, NULL, '2025-09-10 23:16:52', '2025-09-10 23:16:52'),
(3, 'Super Admin', 'superadmin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, NULL, '2025-09-10 23:18:05', '2025-09-10 23:18:05'),
(4, 'Client User', 'client@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 9, 1, NULL, '2025-09-10 23:18:05', '2025-09-10 23:18:05');

-- Insert audits
INSERT INTO `audits` (`user_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 'user_login', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (example)', '2025-09-25 10:00:00'),
(1, 'user_update', 'Updated user profile information', '127.0.0.1', 'Mozilla/5.0 (example)', '2025-09-25 10:05:00'),
(2, 'role_create', 'Created new role: moderator', '127.0.0.1', 'Mozilla/5.0 (example)', '2025-09-25 10:10:00');
