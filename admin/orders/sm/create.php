<?php
require_once '../../../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../../../auth/login.php');
    exit;
}

// Check if user has permission to create orders (superadmin, accounts, or client)
$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['superadmin', 'accounts'];
$isClient = in_array($userRole, ['client', 'client_admin']);

if (!$isClient && !in_array($userRole, $allowedRoles)) {
    setFlash('error', 'You do not have permission to create orders.');
    header('Location: ../../../admin/dashboard.php');
    exit;
}

// Check tour_id from URL parameter
$tourId = $_GET['tour_id'] ?? null;

// For now, this is a placeholder for the Static/Motion order form
// In a complete implementation, this would have similar functionality to the AV order form
// but tailored for static/motion orders

$title = "Static/Motion Order Form - " . SITE_NAME;
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3>Static/Motion Order Form</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <p>Static/Motion order form is under development.</p>
                    <p>This will include specific fields for static and motion graphics orders.</p>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="../../../admin/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                    <?php if ($tourId): ?>
                        <a href="../../../admin/tours/view.php?id=<?php echo urlencode($tourId); ?>" class="btn btn-outline-primary">Back to Tour</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../../includes/layout.php';
?>