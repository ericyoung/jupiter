<?php
// Clear any previous output and start fresh
if (ob_get_level()) {
    ob_clean();
}
header('Content-Type: application/json');

// Initialize variables
$users = [];

// Suppress any errors/warnings and capture them
$errors = [];
set_error_handler(function($severity, $message, $file, $line) {
    $errors[] = $message;
});

try {
    // Use the correct path from the root directory
    require_once __DIR__ . '/../../includes/functions.php';

    // Check if user is logged in
    if (!isset($_SESSION) || !isLoggedIn()) {
        throw new Exception('Not authenticated');
    }

    // Check if user has permission to view users (superadmin, executive, or accounts)
    $userRole = $_SESSION['role'] ?? '';
    $allowedRoles = ['superadmin', 'executive', 'accounts'];
    if (!in_array($userRole, $allowedRoles)) {
        throw new Exception('Not authorized');
    }

    // Get company ID from query parameter
    $companyId = $_GET['company_id'] ?? '';

    if (empty($companyId)) {
        throw new Exception('No company ID provided');
    }

    // Get users associated with the company
    global $pdo;
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE company_id = ? AND is_active = 1 ORDER BY name");
        $stmt->execute([$companyId]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        throw new Exception('Database connection failed');
    }

    echo json_encode($users);
} catch (Exception $e) {
    // If there's an error, return a proper JSON response
    echo json_encode(['error' => $e->getMessage()]);
}

// Restore error handler
restore_error_handler();
?>