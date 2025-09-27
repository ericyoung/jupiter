<?php
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

setCustomBreadcrumbs([
    ['name' => 'Dashboard', 'url' => getRelativePath('admin/dashboard.php')],
    ['name' => 'Orders', 'url' => '']
]);

// Check if user has admin access (superadmin, executive, or accounts)
$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['superadmin', 'executive', 'accounts'];
if (!in_array($userRole, $allowedRoles)) {
    setFlash('error', 'You do not have permission to view orders.');
    header('Location: ../admin/dashboard.php');
    exit;
}

// Get search query
$searchQuery = $_GET['search'] ?? '';

// Build the query with joins to search across multiple tables
global $pdo;

// Base query with joins
$baseQuery = "SELECT o.*, c.company_name, 
              CONCAT(oav.id) AS av_order_ref, CONCAT(osm.id) AS sm_order_ref
              FROM orders o
              LEFT JOIN companies c ON o.company_id = c.id
              LEFT JOIN orders_av oav ON o.av_order_id = oav.id
              LEFT JOIN orders_sm osm ON o.sm_order_id = osm.id";

// Add search conditions if a search query is provided
$whereClause = "1=1";  // Start with a condition that's always true
$params = [];

if (!empty($searchQuery)) {
    $whereClause .= " AND (c.company_name LIKE ? OR o.order_number LIKE ?)";
    $searchTerm = '%' . $searchQuery . '%';
    $params = [$searchTerm, $searchTerm];
}

$fullQuery = $baseQuery . " WHERE " . $whereClause . " ORDER BY o.created_at DESC";

try {
    $stmt = $pdo->prepare($fullQuery);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlash('error', 'Error retrieving orders: ' . $e->getMessage());
    $orders = [];
}

$title = "Manage Orders - " . SITE_NAME;
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3>Manage Orders</h3>
                <a href="create.php" class="btn btn-primary">Create New Order</a>
            </div>
            <div class="card-body">
                <!-- Search Form -->
                <form method="GET" class="mb-4">
                    <div class="row">
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="search" placeholder="Search by company or order number..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">Search</button>
                        </div>
                    </div>
                </form>

                <?php if (empty($orders)): ?>
                    <div class="alert alert-info">
                        <?php if (!empty($searchQuery)): ?>
                            No orders found matching your search for "<?php echo htmlspecialchars($searchQuery); ?>".
                            <a href="list.php">Clear search</a>
                        <?php else: ?>
                            No orders found.
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Company</th>
                                <th>AV Order</th>
                                <th>SM Order</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td><?php echo htmlspecialchars($order['company_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($order['av_order_id']): ?>
                                        <span class="badge bg-info">AV-<?php echo htmlspecialchars($order['av_order_ref'] ?? $order['av_order_id']); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">None</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($order['sm_order_id']): ?>
                                        <span class="badge bg-info">SM-<?php echo htmlspecialchars($order['sm_order_ref'] ?? $order['sm_order_id']); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">None</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="view.php?id=<?php echo urlencode($order['id']); ?>" class="btn btn-sm btn-info">View</a>
                                        <?php if ($order['av_order_id']): ?>
                                            <a href="av/view.php?id=<?php echo urlencode($order['av_order_id']); ?>" class="btn btn-sm btn-outline-info">View AV</a>
                                        <?php endif; ?>
                                        <?php if ($order['sm_order_id']): ?>
                                            <a href="sm/view.php?id=<?php echo urlencode($order['sm_order_id']); ?>" class="btn btn-sm btn-outline-info">View SM</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../includes/layout.php';
?>
