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

// Get all companies from the database
global $pdo;
$stmt = $pdo->query("SELECT id, company_name, contact_email, primary_phone, enabled, created_at FROM companies ORDER BY created_at DESC");
$companies = $stmt->fetchAll();

// Set breadcrumbs for this page
setCustomBreadcrumbs([
    ['name' => 'Dashboard', 'url' => getRelativePath('admin/dashboard.php')],
    ['name' => 'Companies', 'url' => ''], // Current page, not clickable
]);

$title = "Manage Companies - " . SITE_NAME;
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3>Manage Companies</h3>
                <a href="create.php" class="btn btn-primary">Create New Company</a>
            </div>
            <div class="card-body">
                <p>Manage company records and their associated users.</p>
                
                <?php if (empty($companies)): ?>
                    <div class="alert alert-info">No companies found. <a href="create.php">Create the first company</a>.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($companies as $company): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($company['id']); ?></td>
                                <td><?php echo htmlspecialchars($company['company_name']); ?></td>
                                <td><?php echo htmlspecialchars($company['contact_email'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($company['primary_phone'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($company['enabled']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($company['created_at']); ?></td>
                                <td>
                                    <a href="view.php?id=<?php echo urlencode($company['id']); ?>" class="btn btn-sm btn-info">View</a>
                                    <a href="edit.php?id=<?php echo urlencode($company['id']); ?>" class="btn btn-sm btn-primary">Edit</a>
                                    <a href="delete.php?id=<?php echo urlencode($company['id']); ?>" class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this company?')">Delete</a>
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