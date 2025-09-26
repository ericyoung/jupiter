<?php
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

// Check if user has admin role (not a client role)
if (isClient()) {
    // If user has a client role, redirect to client dashboard
    header('Location: ../clients/dashboard.php');
    exit;
}

setCustomBreadcrumbs([]);

$title = "Admin Dashboard - " . SITE_NAME;
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3>Admin Dashboard</h3>
            </div>
            <div class="card-body">
                <h4>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h4>
                <p>This is your admin dashboard. Here you can manage the system and access admin-specific features.</p>

                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card bg-dark text-light h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">User Management</h5>
                                <p class="card-text flex-grow-1">Manage users, roles, and permissions.</p>
                                <a href="<?php echo getRelativePath('admin/users/list.php'); ?>" class="btn btn-primary mt-auto">Manage Users</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-dark text-light h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">Role Management</h5>
                                <p class="card-text flex-grow-1">Create and manage user roles and hierarchies.</p>
                                <a href="<?php echo getRelativePath('admin/roles/list.php'); ?>" class="btn btn-primary mt-auto">Manage Roles</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-dark text-light h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">System Settings</h5>
                                <p class="card-text flex-grow-1">Configure system-wide settings.</p>
                                <a href="<?php echo getRelativePath('admin/settings/dashboard.php'); ?>" class="btn btn-primary mt-auto">Configure</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-dark text-light h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">Audit Logs</h5>
                                <p class="card-text flex-grow-1">View system activity and user actions.</p>
                                <a href="<?php echo getRelativePath('admin/audits.php'); ?>" class="btn btn-primary mt-auto">View Logs</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>
