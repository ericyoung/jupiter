<?php
require_once '../../../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../../../auth/login.php');
    exit;
}

// Check if user has admin access (superadmin, executive, or accounts)
$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['superadmin', 'executive', 'accounts'];
if (!in_array($userRole, $allowedRoles)) {
    setFlash('error', 'You do not have permission to view orders.');
    header('Location: ../../dashboard.php');
    exit;
}

// Get order ID from query parameter
$orderId = $_GET['id'] ?? '';
if (empty($orderId)) {
    setFlash('error', 'No order ID provided.');
    header('Location: ../list.php');
    exit;
}

// Get order data
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM orders_sm WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Order not found.');
    header('Location: ../list.php');
    exit;
}

$title = "View SM Order - " . SITE_NAME;
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3>View SM Order: <?php echo htmlspecialchars($order['id']); ?></h3>
                <a href="../list.php" class="btn btn-sm btn-secondary">Back to Orders</a>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <p>Static/Motion order details are under development.</p>
                    <p>Order ID: <?php echo htmlspecialchars($order['id']); ?></p>
                    <p>Created: <?php echo htmlspecialchars($order['created_at']); ?></p>
                    <?php if ($order['updated_at'] !== $order['created_at']): ?>
                        <p>Updated: <?php echo htmlspecialchars($order['updated_at']); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="../list.php" class="btn btn-secondary">Back to Orders List</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../../includes/layout.php';
?>