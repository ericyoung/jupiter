<?php
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit;
}

// Check if user is a superadmin
if (!isSuperAdmin()) {
    setFlash('error', 'Only superadmins can delete roles.');
    header('Location: ../dashboard.php');
    exit;
}

// Get role ID from query parameter
$roleId = $_GET['id'] ?? '';
if (empty($roleId)) {
    setFlash('error', 'No role ID provided.');
    header('Location: list.php');
    exit;
}

// Get role data
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM roles WHERE id = ?");
$stmt->execute([$roleId]);
$role = $stmt->fetch();

if (!$role) {
    setFlash('error', 'Role not found.');
    header('Location: list.php');
    exit;
}

// Prevent deletion of superadmin role
if ($role['name'] === 'superadmin') {
    setFlash('error', 'Cannot delete superadmin role.');
    header('Location: list.php');
    exit;
}

// Check if there are users with this role
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role_id = ?");
$stmt->execute([$role['id']]);
$userCount = $stmt->fetch()['count'];

if ($userCount > 0) {
    setFlash('error', 'Cannot delete role: there are ' . $userCount . ' user(s) with this role.');
    header('Location: list.php');
    exit;
}

// Delete the role
$stmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
$result = $stmt->execute([$roleId]);

if ($result) {
    setFlash('success', 'Role deleted successfully!');
} else {
    setFlash('error', 'Failed to delete role.');
}

// Redirect back to roles list
header('Location: list.php');
exit;
?>