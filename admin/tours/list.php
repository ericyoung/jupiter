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
    setFlash('error', 'You do not have permission to manage tours.');
    header('Location: ../dashboard.php');
    exit;
}

// Get all tours
global $pdo;
$stmt = $pdo->query("SELECT * FROM tours ORDER BY created_at DESC");
$tours = $stmt->fetchAll();

$title = "Manage Tours - " . SITE_NAME;
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3>Manage Tours</h3>
                <a href="create.php" class="btn btn-primary">Create New Tour</a>
            </div>
            <div class="card-body">
                <?php if (empty($tours)): ?>
                    <div class="alert alert-info">No tours found.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Headliner</th>
                                <th>Support</th>
                                <th>Active</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tours as $tour): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($tour['id']); ?></td>
                                <td><?php echo htmlspecialchars($tour['headliner']); ?></td>
                                <td><?php echo htmlspecialchars($tour['support'] ?? ''); ?></td>
                                <td>
                                    <?php if ($tour['active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($tour['created_at']); ?></td>
                                <td>
                                    <a href="edit.php?id=<?php echo urlencode($tour['id']); ?>" class="btn btn-sm btn-primary">Edit</a>
                                    <a href="view.php?id=<?php echo urlencode($tour['id']); ?>" class="btn btn-sm btn-info">View</a>
                                    <?php if (in_array($userRole, ['superadmin', 'accounts'])): ?>
                                        <!-- Add order creation buttons for eligible roles -->
                                        <a href="../orders/av/create.php?tour_id=<?php echo urlencode($tour['id']); ?>" class="btn btn-sm btn-success">Start AV Order</a>
                                    <?php endif; ?>
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