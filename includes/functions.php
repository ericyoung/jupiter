<?php
require_once 'config.php'; // Load config first

// Initialize session with secure settings
function initSecureSession() {
    $session_name = 'JUPITER_SESSION';
    session_name($session_name);

    $https_only = HTTPS_ONLY;
    $domain = parse_url(SITE_URL, PHP_URL_HOST) ?: $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Set secure session cookie parameters
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'domain' => $domain,
        'secure' => $https_only,  // Only send over HTTPS in production
        'httponly' => true,       // Prevent XSS attacks
        'samesite' => 'Strict'    // Prevent CSRF attacks
    ]);

    session_start();

    // Additional security: Regenerate session ID periodically to prevent session fixation
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 300) { // Regenerate every 5 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// Initialize the secure session
initSecureSession();

require_once 'db.php';

/**
 * Redirect user based on their role after login
 */
function redirectUser() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: auth/login.php');
        exit;
    }

    $role = $_SESSION['role'] ?? '';

    if ($role === 'client' || $role === 'client_admin') {
        header('Location: ../clients/dashboard.php');
    } else {
        header('Location: ../admin/dashboard.php');
    }
    exit;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is already logged in and redirect them
 */
function checkAlreadyLoggedIn() {
    if (isLoggedIn()) {
        // Determine where to redirect based on role
        $role = $_SESSION['role'] ?? '';

        if ($role === 'client' || $role === 'client_admin') {
            if (strpos($_SERVER['REQUEST_URI'], 'auth/') !== false) {
                // If we're in the auth directory, go up one level to clients
                header('Location: ../clients/dashboard.php');
            } else {
                header('Location: clients/dashboard.php');
            }
        } else {
            if (strpos($_SERVER['REQUEST_URI'], 'auth/') !== false) {
                // If we're in the auth directory, go up one level to admin
                header('Location: ../admin/dashboard.php');
            } else {
                header('Location: admin/dashboard.php');
            }
        }
        exit;
    }
}

/**
 * Generate a random token
 */
function generateToken($length = TOKEN_LENGTH) {
    return bin2hex(random_bytes($length));
}

/**
 * Hash a password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify a password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Sanitize input
 */
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

/**
 * Get user by email
 */
function getUserByEmail($email) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT u.*, r.name as role FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

/**
 * Get user by ID
 */
function getUserById($id) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT u.*, r.name as role, r.display_name, r.is_client_role, r.hierarchy_level FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Register a new user
 */
function registerUser($name, $email, $password) {
    global $pdo;

    // Get the role_id for 'client' role
    $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'client'");
    $roleStmt->execute();
    $role = $roleStmt->fetch();

    if (!$role) {
        return false; // Role not found
    }

    $hashedPassword = hashPassword($password);
    $token = generateToken();
    $roleId = $role['id'];

    // Set is_active to 0 (not active) until email confirmation
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role_id, is_active, activation_token) VALUES (?, ?, ?, ?, 0, ?)");
    $result = $stmt->execute([$name, $email, $hashedPassword, $roleId, $token]);

    if ($result) {
        $userId = $pdo->lastInsertId();

        // Send confirmation email
        sendConfirmationEmail($email, $name, $token);

        return $userId;
    }

    return false;
}

/**
 * Login user
 */
function loginUser($email, $password) {
    global $pdo;

    $user = getUserByEmail($email);

    if ($user && verifyPassword($password, $user['password'])) {
        // Check if user is active
        if (!$user['is_active']) {
            // User exists but is not active (not confirmed)
            setFlash('error', 'Please confirm your email address before logging in. Check your email for the confirmation link.');
            return false;
        }

        // User is active and password is correct
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role']; // role name is selected in the query
        $_SESSION['name'] = $user['name'];
        return true;
    }

    // Invalid credentials
    return false;
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Check if user has client role
 */
function isClient() {
    return hasRole('client') || hasRole('client_admin');
}

/**
 * Check if user has admin role
 */
function isAdmin() {
    return !isClient(); // All non-client roles are considered admin
}

/**
 * Get role information by name
 */
function getRoleByName($roleName) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM roles WHERE name = ?");
    $stmt->execute([$roleName]);
    return $stmt->fetch();
}

/**
 * Get all roles
 */
function getAllRoles() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM roles ORDER BY hierarchy_level DESC");
    return $stmt->fetchAll();
}

/**
 * Check if user is a superadmin
 */
function isSuperAdmin() {
    return hasRole('superadmin');
}

/**
 * Get user's role details
 */
function getUserRoleDetails($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT r.* FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

/**
 * Generate the application root path for redirects
 */
function getAppRootPath() {
    // Get the directory of the current script relative to document root
    $script_dir = dirname($_SERVER['SCRIPT_NAME']);

    // Clean up the directory path
    $clean_dir = trim($script_dir, '/');

    // If we're already at root level, return empty string
    if ($clean_dir === '') {
        return '';
    }

    // Count directory levels to determine how to get to root
    $dir_parts = explode('/', $clean_dir);
    $levels = count(array_filter($dir_parts));

    // If we're in a subdirectory, we need to calculate the base path to the app
    // Actually, we just need to return the base path for the application
    // Let's use a simpler approach based on the script name
    return dirname($_SERVER['SCRIPT_NAME']);
}

/**
 * Logout user
 */
function logoutUser() {
    // Set the flash message while session is still active
    setFlash('success', 'You have been successfully logged out.');
    // Clear user-specific session data but keep session active for flash messages
    unset($_SESSION['user_id']);
    unset($_SESSION['role']);
    unset($_SESSION['name']);
    // Redirect to logout page using the correct path
    // We can use an absolute path from the web server root
    $path = '/auth/logout.php';
    header('Location: ' . $path);
    exit;
}

// Handle logout action if requested
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logoutUser();
}

/**
 * Check if email exists in database
 */
function emailExists($email) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    return $stmt->fetch() ? true : false;
}

/**
 * Generate password reset token
 */
function generateResetToken($email) {
    global $pdo;

    $token = generateToken();
    $expires = date("Y-m-d H:i:s", strtotime("+1 hour")); // Token expires in 1 hour

    $stmt = $pdo->prepare("UPDATE users SET activation_token = ? WHERE email = ?");
    $result = $stmt->execute([$token, $email]);

    return $result ? $token : false;
}

/**
 * Verify password reset token
 */
function verifyResetToken($token) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE activation_token = ? AND is_active = 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // Check if token has expired (implementation needed based on your requirements)
        // For now, we'll just return the user if token exists
        return $user;
    }

    return false;
}

/**
 * Reset user password
 */
function resetPassword($token, $password) {
    global $pdo;

    $hashedPassword = hashPassword($password);

    $stmt = $pdo->prepare("UPDATE users SET password = ?, activation_token = NULL WHERE activation_token = ?");
    $result = $stmt->execute([$hashedPassword, $token]);

    return $result;
}

/**
 * Activate account with token
 */
function activateAccount($token) {
    global $pdo;

    $stmt = $pdo->prepare("UPDATE users SET is_active = 1, activation_token = NULL WHERE activation_token = ?");
    $result = $stmt->execute([$token]);

    return $result;
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if request is valid (prevent simple script attacks)
 */
function isValidRequest() {
    return isset($_SERVER['HTTP_HOST']) &&
           (!empty($_SERVER['HTTP_REFERER']) || $_SERVER['REQUEST_METHOD'] === 'GET');
}

/**
 * Generate proper relative path from any location to target file/folder
 * @param string $target Target file or folder
 * @return string Proper relative path
 */
function getRelativePath($target) {
    // Get the script's directory path
    $script_dir = dirname($_SERVER['SCRIPT_NAME']);

    // Remove leading slash and get directory levels
    $clean_dir = trim($script_dir, '/');

    // If we're at the root level (empty string after trim), no prefix needed
    if ($clean_dir === '') {
        return $target;
    }

    // Count directory levels by counting slashes + 1
    // For '/admin/dashboard.php' -> dirname is '/admin' -> after trim: 'admin' -> 0 slashes + 1 = 1 level
    // For '/app/admin/dashboard.php' -> dirname is '/app/admin' -> after trim: 'app/admin' -> 1 slash + 1 = 2 levels
    $dir_parts = explode('/', $clean_dir);
    $levels = count(array_filter($dir_parts)); // Count non-empty directory names

    // Create the prefix to go back to root
    $prefix = str_repeat('../', $levels);
    return $prefix . ltrim($target, '/');
}

/**
 * Log an action to the audit trail
 * @param int $userId The ID of the user performing the action
 * @param string $action The type of action performed
 * @param string $description Optional description of the action
 * @return bool True if the audit was logged successfully, false otherwise
 */
function logAudit($userId, $action, $description = '') {
    global $pdo;

    try {
        $stmt = $pdo->prepare("INSERT INTO audits (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $result = $stmt->execute([$userId, $action, $description, $ipAddress, $userAgent]);

        return $result;
    } catch (PDOException $e) {
        error_log("Audit logging failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get audit logs for a specific user
 * @param int $userId The ID of the user to get logs for
 * @param int $limit Number of records to return (default 50)
 * @param int $offset Number of records to skip (default 0)
 * @param string $startDate Optional start date filter (format: YYYY-MM-DD)
 * @param string $endDate Optional end date filter (format: YYYY-MM-DD)
 * @return array Array of audit records
 */
function getUserAudits($userId, $limit = 50, $offset = 0, $startDate = '', $endDate = '') {
    global $pdo;

    try {
        // Build the query with optional date filtering
        $whereClause = "a.user_id = ?";
        $params = [$userId];

        if (!empty($startDate)) {
            $whereClause .= " AND DATE(a.created_at) >= ?";
            $params[] = $startDate;
        }
        if (!empty($endDate)) {
            $whereClause .= " AND DATE(a.created_at) <= ?";
            $params[] = $endDate;
        }

        $query = "SELECT a.*, u.name as username FROM audits a LEFT JOIN users u ON a.user_id = u.id WHERE $whereClause ORDER BY a.created_at DESC LIMIT ? OFFSET ?";
        $paramsWithLimit = array_merge($params, [$limit, $offset]);

        $stmt = $pdo->prepare($query);
        $stmt->execute($paramsWithLimit);

        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Audit retrieval failed: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all audit logs
 * @param int $limit Number of records to return (default 50)
 * @param int $offset Number of records to skip (default 0)
 * @param string $startDate Optional start date filter (format: YYYY-MM-DD)
 * @param string $endDate Optional end date filter (format: YYYY-MM-DD)
 * @return array Array of audit records
 */
function getAllAudits($limit = 50, $offset = 0, $startDate = '', $endDate = '') {
    global $pdo;

    try {
        // Build the query with optional date filtering
        $whereClause = "1=1";
        $params = [];

        if (!empty($startDate)) {
            $whereClause .= " AND DATE(a.created_at) >= ?";
            $params[] = $startDate;
        }
        if (!empty($endDate)) {
            $whereClause .= " AND DATE(a.created_at) <= ?";
            $params[] = $endDate;
        }

        $query = "SELECT a.*, u.name as username FROM audits a LEFT JOIN users u ON a.user_id = u.id WHERE $whereClause ORDER BY a.created_at DESC LIMIT ? OFFSET ?";
        $paramsWithLimit = array_merge($params, [$limit, $offset]);

        $stmt = $pdo->prepare($query);
        $stmt->execute($paramsWithLimit);

        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Audit retrieval failed: " . $e->getMessage());
        return [];
    }
}

/**
 * Set a flash message
 * @param string $type Type of message (success, error, warning, info)
 * @param string $message The message content
 */
function setFlash($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }

    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message,
        'timestamp' => time()
    ];
}

/**
 * Get and clear all flash messages
 * @return array Array of flash messages
 */
function getFlash() {
    if (isset($_SESSION['flash_messages'])) {
        $messages = $_SESSION['flash_messages'];
        unset($_SESSION['flash_messages']);
        return $messages;
    }
    return [];
}

/**
 * Clear all flash messages
 */
function clearFlash() {
    unset($_SESSION['flash_messages']);
}

/**
 * Send an email (in production) or log it (in development)
 */
function sendEmail($to, $subject, $body, $additionalData = []) {
    if (ENVIRONMENT === 'development') {
        // In development, log the email instead of sending it
        static $emailTracker = null; // Use static variable to initialize once
        if ($emailTracker === null) {
            require_once dirname(__FILE__) . '/../dev/email_tracker.php';
            $emailTracker = new DevEmailTracker();
        }
        $emailTracker->logEmail($to, $subject, $body, $additionalData);
        return true;
    } else {
        // In production, send the actual email
        // This is a simplified implementation - in a real app you'd use PHPMailer or similar
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: ' . SITE_NAME . ' <no-reply@' . $_SERVER['HTTP_HOST'] . '>' . "\r\n";

        return mail($to, $subject, $body, $headers);
    }
}

/**
 * Send registration confirmation email
 */
function sendConfirmationEmail($email, $name, $token) {
    $subject = 'Confirm Your Account - ' . SITE_NAME;

    // Build confirmation URL
    $siteUrl = rtrim(SITE_URL, '/');
    $confirmationUrl = $siteUrl . '/includes/functions.php?action=confirm&token=' . urlencode($token);

    $body = "
    <html>
    <body>
        <h2>Welcome to " . SITE_NAME . ", {$name}!</h2>
        <p>Thank you for registering. Please confirm your email address by clicking the link below:</p>
        <p><a href='{$confirmationUrl}'>Confirm Your Email</a></p>
        <p>If the button doesn't work, copy and paste this URL into your browser:</p>
        <p>{$confirmationUrl}</p>
        <p>This link will expire in 24 hours.</p>
        <p>If you didn't register for an account, you can safely ignore this email.</p>
    </body>
    </html>
    ";

    return sendEmail($email, $subject, $body, ['type' => 'registration_confirmation']);
}

/**
 * Handle email confirmation action
 */
if (isset($_GET['action']) && $_GET['action'] === 'confirm') {
    $token = $_GET['token'] ?? '';

    if (!empty($token)) {
        $result = activateAccount($token);
        if ($result) {
            // Redirect to login with success message
            setFlash('success', 'Your account has been confirmed successfully! You can now login.');
            header('Location: ../auth/login.php');
        } else {
            setFlash('error', 'Invalid or expired confirmation token.');
            header('Location: ../auth/register.php');
        }
    } else {
        setFlash('error', 'No confirmation token provided.');
        header('Location: ../index.php');
    }
    exit;
}

// /**
//  * Generate breadcrumbs based on the current page path
//  * @param array $additionalCrumbs Additional breadcrumbs to append
//  * @return string HTML for the breadcrumb navigation
//  */
// function generateBreadcrumbs($additionalCrumbs = []) {
//     $currentPage = $_SERVER['REQUEST_URI'] ?? '';
//
//     // Don't show breadcrumbs on the main index page
//     if (strpos($currentPage, '/index.php') !== false || empty($currentPage) || $currentPage === '/') {
//         return '';
//     }
//
//     // Define the base navigation structure
//     $breadcrumbs = [];
//
//     // Determine the page type and build appropriate path
//     if (strpos($currentPage, '/admin/') !== false) {
//         // Don't show breadcrumbs on the main admin dashboard
//         if (strpos($currentPage, '/admin/dashboard.php') !== false) {
//             return '';
//         }
//
//         // Always show Admin as the first breadcrumb for all admin pages except the dashboard
//         $breadcrumbs[] = ['name' => 'Dashboard', 'url' => getRelativePath('admin/dashboard.php')];
//
//         if (strpos($currentPage, '/admin/users/') !== false) {
//             // Always add Users as a link to the list page, regardless of current page
//             $breadcrumbs[] = ['name' => 'Users', 'url' => getRelativePath('admin/users/list.php')];
//
//             if (strpos($currentPage, '/admin/users/list.php') !== false) {
//                 $breadcrumbs[] = ['name' => 'List', 'url' => ''];
//             } elseif (strpos($currentPage, '/admin/users/create.php') !== false) {
//                 $breadcrumbs[] = ['name' => 'Create', 'url' => ''];
//             } elseif (strpos($currentPage, '/admin/users/edit.php') !== false) {
//                 $breadcrumbs[] = ['name' => 'Edit', 'url' => ''];
//             }
//         } elseif (strpos($currentPage, '/admin/roles/') !== false) {
//             // Always add Roles as a link to the list page, regardless of current page
//             $breadcrumbs[] = ['name' => 'Roles', 'url' => getRelativePath('admin/roles/list.php')];
//
//             if (strpos($currentPage, '/admin/roles/list.php') !== false) {
//                 $breadcrumbs[] = ['name' => 'List', 'url' => ''];
//             } elseif (strpos($currentPage, '/admin/roles/create.php') !== false) {
//                 $breadcrumbs[] = ['name' => 'Create', 'url' => ''];
//             } elseif (strpos($currentPage, '/admin/roles/edit.php') !== false) {
//                 $breadcrumbs[] = ['name' => 'Edit', 'url' => ''];
//             }
//         } elseif (strpos($currentPage, '/admin/settings/') !== false) {
//             // Always add Settings as a link to the settings dashboard, regardless of current page
//             $breadcrumbs[] = ['name' => 'Settings', 'url' => getRelativePath('admin/settings/dashboard.php')];
//
//             if (strpos($currentPage, '/admin/settings/dashboard.php') !== false) {
//                 $breadcrumbs[] = ['name' => 'Dashboard', 'url' => ''];
//             }
//         } elseif (strpos($currentPage, '/admin/audits.php') !== false) {
//             $breadcrumbs[] = ['name' => 'Audit Logs', 'url' => ''];
//         }
//     } elseif (strpos($currentPage, '/auth/') !== false) {
//         // Always add Authentication as a link to the login page, regardless of current page
//         $breadcrumbs[] = ['name' => 'Authentication', 'url' => getRelativePath('auth/login.php')];
//
//         if (strpos($currentPage, '/auth/login.php') !== false) {
//             $breadcrumbs[] = ['name' => 'Login', 'url' => ''];
//         } elseif (strpos($currentPage, '/auth/register.php') !== false) {
//             $breadcrumbs[] = ['name' => 'Register', 'url' => ''];
//         } elseif (strpos($currentPage, '/auth/forgot-password.php') !== false) {
//             $breadcrumbs[] = ['name' => 'Forgot Password', 'url' => ''];
//         }
//     } elseif (strpos($currentPage, '/clients/') !== false) {
//         // Don't show breadcrumbs on the main client dashboard
//         if (strpos($currentPage, '/clients/dashboard.php') !== false) {
//             return '';
//         }
//
//         // Always add Client as a link to the client dashboard, regardless of current page
//         $breadcrumbs[] = ['name' => 'Client', 'url' => getRelativePath('clients/dashboard.php')];
//
//         if (strpos($currentPage, '/clients/profile.php') !== false) {
//             $breadcrumbs[] = ['name' => 'Profile', 'url' => ''];
//         }
//     } elseif (strpos($currentPage, '/admin/audits.php') !== false) {
//         $breadcrumbs[] = ['name' => 'Audit Logs', 'url' => ''];
//     } elseif (strpos($currentPage, '/auth/') !== false) {
//         // Always add Authentication as a link to the login page, regardless of current page
//         $breadcrumbs[] = ['name' => 'Authentication', 'url' => getRelativePath('auth/login.php')];
//
//         if (strpos($currentPage, '/auth/login.php') !== false) {
//             $breadcrumbs[] = ['name' => 'Login', 'url' => ''];
//         } elseif (strpos($currentPage, '/auth/register.php') !== false) {
//             $breadcrumbs[] = ['name' => 'Register', 'url' => ''];
//         } elseif (strpos($currentPage, '/auth/forgot-password.php') !== false) {
//             $breadcrumbs[] = ['name' => 'Forgot Password', 'url' => ''];
//         }
//     } elseif (strpos($currentPage, '/clients/') !== false) {
//         // Don't show breadcrumbs on the main client dashboard
//         if (strpos($currentPage, '/clients/dashboard.php') !== false) {
//             return '';
//         }
//
//         // Always add Client as a link to the client dashboard, regardless of current page
//         $breadcrumbs[] = ['name' => 'Client', 'url' => getRelativePath('clients/dashboard.php')];
//
//         if (strpos($currentPage, '/clients/profile.php') !== false) {
//             $breadcrumbs[] = ['name' => 'Profile', 'url' => ''];
//         }
//     }
//
//     // Add any additional breadcrumbs passed in
//     $breadcrumbs = array_merge($breadcrumbs, $additionalCrumbs);
//
//     // Generate the HTML
//     $html = '<nav aria-label="breadcrumb">';
//     $html .= '<ol class="breadcrumb">';
//
//     foreach ($breadcrumbs as $index => $crumb) {
//         $isActive = ($index === count($breadcrumbs) - 1 && empty($crumb['url']));
//         $html .= '<li class="breadcrumb-item ' . ($isActive ? 'active' : '') . '">';
//
//         if ($isActive) {
//             $html .= $crumb['name'];
//         } else {
//             $html .= '<a href="' . $crumb['url'] . '">' . $crumb['name'] . '</a>';
//         }
//
//         $html .= '</li>';
//     }
//
//     $html .= '</ol>';
//     $html .= '</nav>';
//
//     return $html;
// }

/**
 * Set custom breadcrumbs for the current page
 * @param array $breadcrumbs Array of breadcrumb items with 'url' and 'name' keys
 */
function setCustomBreadcrumbs($breadcrumbs) {
    $_SESSION['custom_breadcrumbs'] = $breadcrumbs;
}

/**
 * Get custom breadcrumbs for the current page
 * @return array
 */
function getCustomBreadcrumbs() {
    return $_SESSION['custom_breadcrumbs'] ?? [];
}

/**
 * Generate custom breadcrumbs HTML based on user-defined breadcrumbs
 * @return string HTML for breadcrumbs
 */
function generateCustomBreadcrumbs() {
    $breadcrumbs = getCustomBreadcrumbs();

    if (empty($breadcrumbs)) {
        return '';
    }

    $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';

    foreach ($breadcrumbs as $index => $breadcrumb) {
        $isLast = ($index === count($breadcrumbs) - 1);

        $html .= '<li class="breadcrumb-item';
        if ($isLast) {
            $html .= ' active" aria-current="page';
        }
        $html .= '">';

        if (!empty($breadcrumb['url']) && !$isLast) {
            $html .= '<a href="' . htmlspecialchars($breadcrumb['url']) . '">' . htmlspecialchars($breadcrumb['name']) . '</a>';
        } else {
            $html .= htmlspecialchars($breadcrumb['name']);
        }

        $html .= '</li>';
    }

    $html .= '</ol></nav>';

    return $html;
}

/**
 * Clear custom breadcrumbs
 */
function clearCustomBreadcrumbs() {
    unset($_SESSION['custom_breadcrumbs']);
}
