<?php
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit;
}

// Check if user has admin access (superadmin, executive, or accounts)
$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['superadmin', 'executive', 'accounts'];
if (!in_array($userRole, $allowedRoles)) {
    setFlash('error', 'You do not have permission to view orders.');
    header('Location: ../dashboard.php');
    exit;
}

// Get order ID from query parameter
$orderId = $_GET['id'] ?? '';
if (empty($orderId)) {
    setFlash('error', 'No order ID provided.');
    header('Location: list.php');
    exit;
}

// Get order data with related information
global $pdo;
$stmt = $pdo->prepare("SELECT o.*, c.company_name,
                       CONCAT(oav.id) AS av_order_ref, CONCAT(osm.id) AS sm_order_ref
                       FROM orders o
                       LEFT JOIN companies c ON o.company_id = c.id
                       LEFT JOIN orders_av oav ON o.av_order_id = oav.id
                       LEFT JOIN orders_sm osm ON o.sm_order_id = osm.id
                       WHERE o.id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Order not found.');
    header('Location: list.php');
    exit;
}

$title = "View Order - " . SITE_NAME;
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3>View Order: <?php echo htmlspecialchars($order['order_number']); ?></h3>
                <div>
                    <a href="edit.php?id=<?php echo urlencode($order['id']); ?>" class="btn btn-sm btn-primary">Edit Order</a>
                    <a href="list.php" class="btn btn-sm btn-secondary">Back to Orders</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Order Information</h5>
                        <dl class="row">
                            <dt class="col-sm-4">Order Number</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($order['order_number']); ?></dd>
                            
                            <dt class="col-sm-4">Company</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($order['company_name'] ?? 'N/A'); ?></dd>
                            
                            <dt class="col-sm-4">Created</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($order['created_at']); ?></dd>
                            
                            <?php if ($order['updated_at'] !== $order['created_at']): ?>
                                <dt class="col-sm-4">Last Updated</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($order['updated_at']); ?></dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                    
                    <div class="col-md-6">
                        <h5>Order Details</h5>
                        <dl class="row">
                            <dt class="col-sm-5">Audio/Video Order</dt>
                            <dd class="col-sm-7">
                                <?php if ($order['av_order_id']): ?>
                                    <span class="badge bg-info">AV-<?php echo htmlspecialchars($order['av_order_ref']); ?></span>
                                    <a href="av/view.php?id=<?php echo urlencode($order['av_order_id']); ?>" class="btn btn-sm btn-outline-info ms-2">View Details</a>
                                <?php else: ?>
                                    <span class="badge bg-secondary">None</span>
                                <?php endif; ?>
                            </dd>
                            
                            <dt class="col-sm-5">Static/Motion Order</dt>
                            <dd class="col-sm-7">
                                <?php if ($order['sm_order_id']): ?>
                                    <span class="badge bg-info">SM-<?php echo htmlspecialchars($order['sm_order_ref']); ?></span>
                                    <a href="sm/view.php?id=<?php echo urlencode($order['sm_order_id']); ?>" class="btn btn-sm btn-outline-info ms-2">View Details</a>
                                <?php else: ?>
                                    <span class="badge bg-secondary">None</span>
                                <?php endif; ?>
                            </dd>
                            
                            <dt class="col-sm-5">Revised From Order</dt>
                            <dd class="col-sm-7">
                                <?php if ($order['revised_from_order_id']): ?>
                                    <a href="view.php?id=<?php echo urlencode($order['revised_from_order_id']); ?>" class="badge bg-primary">
                                        Order #<?php echo $order['revised_from_order_id']; ?>
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Original</span>
                                <?php endif; ?>
                            </dd>
                        </dl>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <h5>Actions</h5>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php if ($order['av_order_id']): ?>
                                <a href="av/view.php?id=<?php echo urlencode($order['av_order_id']); ?>" class="btn btn-info">View AV Order</a>
                            <?php endif; ?>
                            
                            <?php if ($order['sm_order_id']): ?>
                                <a href="sm/view.php?id=<?php echo urlencode($order['sm_order_id']); ?>" class="btn btn-info">View SM Order</a>
                            <?php endif; ?>
                            
                            <a href="edit.php?id=<?php echo urlencode($order['id']); ?>" class="btn btn-primary">Edit Order</a>
                            <a href="list.php" class="btn btn-secondary">Back to Orders List</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../includes/layout.php';
?>