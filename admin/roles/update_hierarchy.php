<?php
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Check if user is a superadmin
if (!isSuperAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only superadmins can update role hierarchy']);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['roles']) || !is_array($input['roles'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

global $pdo;
$success = true;
$message = '';

try {
    // Use transaction to ensure all updates succeed or fail together
    $pdo->beginTransaction();
    
    foreach ($input['roles'] as $roleData) {
        if (!isset($roleData['id'], $roleData['hierarchy_level'])) {
            throw new Exception('Missing role ID or hierarchy level');
        }
        
        $stmt = $pdo->prepare("UPDATE roles SET hierarchy_level = ? WHERE id = ?");
        $result = $stmt->execute([$roleData['hierarchy_level'], $roleData['id']]);
        
        if (!$result) {
            throw new Exception('Failed to update role: ' . $roleData['id']);
        }
    }
    
    $pdo->commit();
    $message = 'Hierarchy updated successfully';
} catch (Exception $e) {
    $pdo->rollBack();
    $success = false;
    $message = $e->getMessage();
}

echo json_encode([
    'success' => $success,
    'message' => $message
]);
?>