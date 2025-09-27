<?php
// Clear any previous output and start fresh
if (ob_get_level()) {
    ob_clean();
}
header('Content-Type: application/json');

// Initialize variables
$tours = [];

// For debugging, create a log of what's happening
$debug_info = [];

// Suppress any errors/warnings and capture them
$errors = [];
set_error_handler(function($severity, $message, $file, $line) use (&$errors) {
    $errors[] = $message;
});

try {
    // Use the correct path from the root directory
    require_once __DIR__ . '/../../includes/functions.php';

    // Check if user is logged in
    if (!isset($_SESSION) || !isLoggedIn()) {
        throw new Exception('Not authenticated');
    }

    // Check if user has permission to view tours (superadmin, executive, or accounts)
    $userRole = $_SESSION['role'] ?? '';
    $allowedRoles = ['superadmin', 'executive', 'accounts'];
    if (!in_array($userRole, $allowedRoles)) {
        throw new Exception('Not authorized');
    }

    // Get search term from query parameter
    $searchTerm = $_GET['search'] ?? '';
    $debug_info['search_term'] = $searchTerm;

    // Build query based on search term
    global $pdo;
    if ($pdo) {
        if (!empty($searchTerm)) {
            // Create the search pattern with wildcards for partial matching
            $searchPattern = '%' . $searchTerm . '%';
            $debug_info['search_pattern'] = $searchPattern;
            
            $stmt = $pdo->prepare("SELECT id, headliner FROM tours WHERE headliner LIKE ? AND active = 1 ORDER BY headliner LIMIT 10");
            $stmt->execute([$searchPattern]);
        } else {
            $stmt = $pdo->prepare("SELECT id, headliner FROM tours WHERE active = 1 ORDER BY headliner LIMIT 10");
            $stmt->execute();
        }

        $tours = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $debug_info['result_count'] = count($tours);
    } else {
        throw new Exception('Database connection failed');
    }

    // For debugging - you can uncomment the next line to see debug info
    // $tours['debug'] = $debug_info;
    
    echo json_encode($tours);
} catch (Exception $e) {
    // If there's an error, return a proper JSON response
    // For debugging - you can return the error in the response
    echo json_encode(['error' => $e->getMessage(), 'debug' => $debug_info]);
}

// Restore error handler
restore_error_handler();
?>