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
    setFlash('error', 'You do not have permission to view tours.');
    header('Location: ../dashboard.php');
    exit;
}

// Get tour ID from query parameter
$tourId = $_GET['id'] ?? '';
if (empty($tourId)) {
    setFlash('error', 'No tour ID provided.');
    header('Location: list.php');
    exit;
}

// Get tour data
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM tours WHERE id = ?");
$stmt->execute([$tourId]);
$tour = $stmt->fetch();

if (!$tour) {
    setFlash('error', 'Tour not found.');
    header('Location: list.php');
    exit;
}

$title = "View Tour - " . SITE_NAME;
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3>View Tour: <?php echo htmlspecialchars($tour['headliner']); ?></h3>
                <div>
                    <a href="edit.php?id=<?php echo urlencode($tour['id']); ?>" class="btn btn-sm btn-primary">Edit Tour</a>
                    <a href="list.php" class="btn btn-sm btn-secondary">Back to Tours</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Tour Information</h5>
                        <dl class="row">
                            <dt class="col-sm-4">Headliner</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($tour['headliner']); ?></dd>
                            
                            <dt class="col-sm-4">Support</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($tour['support'] ?? ''); ?></dd>
                            
                            <dt class="col-sm-4">Intro Line</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($tour['intro_line'] ?? ''); ?></dd>
                            
                            <dt class="col-sm-4">Outro Line</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($tour['outro_line'] ?? ''); ?></dd>
                            
                            <dt class="col-sm-4">Produced By</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($tour['produced_by'] ?? ''); ?></dd>
                            
                            <dt class="col-sm-4">Active</dt>
                            <dd class="col-sm-8">
                                <?php if ($tour['active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </dd>
                            
                            <dt class="col-sm-4">Created</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($tour['created_at']); ?></dd>
                            
                            <?php if ($tour['updated_at'] !== $tour['created_at']): ?>
                                <dt class="col-sm-4">Last Updated</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($tour['updated_at']); ?></dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                    
                    <div class="col-md-6">
                        <h5>Order Creation</h5>
                        <p>Start a new order for this tour:</p>
                        <?php if (in_array($userRole, ['superadmin', 'accounts'])): ?>
                        <a href="../orders/av/create.php?tour_id=<?php echo urlencode($tour['id']); ?>" class="btn btn-success mb-2 w-100">
                            Start New Audio/Video Order
                        </a>
                        <a href="../orders/sm/create.php?tour_id=<?php echo urlencode($tour['id']); ?>" class="btn btn-success w-100">
                            Start New Static/Motion Order
                        </a>
                        <?php else: ?>
                        <p class="text-muted">You don't have permission to create orders.</p>
                        <?php endif; ?>
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