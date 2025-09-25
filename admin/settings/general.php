<?php
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit;
}

// Check if user has admin role (not a client role)
if (isClient()) {
    // If user has a client role, redirect to client dashboard
    header('Location: ../../clients/dashboard.php');
    exit;
}

setCustomBreadcrumbs([
    ['name' => 'Dashboard', 'url' => getRelativePath('admin/dashboard.php')],
    ['name' => 'Settings', 'url' => getRelativePath('admin/settings/dashboard.php')],
    ['name' => 'General', 'url' => '']
]);

// Check if user has permission to manage settings (superadmin, executive, or accounts)
$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['superadmin', 'executive', 'accounts'];
if (!in_array($userRole, $allowedRoles)) {
    setFlash('error', 'You do not have permission to manage settings.');
    header('Location: ../dashboard.php');
    exit;
}

$title = "General Settings - " . SITE_NAME;
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3>General Settings</h3>
            </div>
            <div class="card-body">
                <h4>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h4>
                <p>Configure general application settings.</p>

                <form method="POST">
                    <div class="mb-3">
                        <label for="siteName" class="form-label">Site Name</label>
                        <input type="text" class="form-control" id="siteName" name="site_name" value="<?php echo htmlspecialchars(SITE_NAME); ?>">
                        <div class="form-text">The name that appears in the title and header</div>
                    </div>

                    <div class="mb-3">
                        <label for="siteDescription" class="form-label">Site Description</label>
                        <textarea class="form-control" id="siteDescription" name="site_description" rows="3">Configure general application settings here</textarea>
                        <div class="form-text">A short description of your application</div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="maintenanceMode" name="maintenance_mode">
                        <label class="form-check-label" for="maintenanceMode">Enable Maintenance Mode</label>
                        <div class="form-text">When enabled, only admins will be able to access the site</div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../includes/layout.php';
?>
