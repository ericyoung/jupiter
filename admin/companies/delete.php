<?php
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit;
}

// Check if user has admin access
if (isClient()) {
    header('Location: ../../clients/dashboard.php');
    exit;
}

// Check if user has permission to manage companies (superadmin, executive, or accounts)
$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['superadmin', 'executive', 'accounts'];
if (!in_array($userRole, $allowedRoles)) {
    setFlash('error', 'You do not have permission to manage companies.');
    header('Location: ../dashboard.php');
    exit;
}

// Get company ID from query parameter
$companyId = $_GET['id'] ?? '';
if (empty($companyId)) {
    setFlash('error', 'No company ID provided.');
    header('Location: list.php');
    exit;
}

// Get company data
global $pdo;
$stmt = $pdo->prepare("SELECT id, name FROM companies WHERE id = ?");
$stmt->execute([$companyId]);
$company = $stmt->fetch();

if (!$company) {
    setFlash('error', 'Company not found.');
    header('Location: list.php');
    exit;
}

// Check if company has associated users
$stmt = $pdo->prepare("SELECT COUNT(*) as user_count FROM users WHERE company_id = ?");
$stmt->execute([$companyId]);
$userCount = (int)$stmt->fetch()['user_count'];

if ($userCount > 0) {
    setFlash('error', "Cannot delete company '{$company['name']}' because it has $userCount associated user(s). Please reassign or delete the users first.");
    header('Location: view.php?id=' . urlencode($companyId));
    exit;
}

// Delete the company
$stmt = $pdo->prepare("DELETE FROM companies WHERE id = ?");
$result = $stmt->execute([$companyId]);

if ($result) {
    setFlash('success', "Company '{$company['name']}' deleted successfully!");
} else {
    setFlash('error', "Failed to delete company '{$company['name']}'.");
}

header('Location: list.php');
exit;
?>