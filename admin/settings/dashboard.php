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
    ['name' => 'Settings', 'url' => '']
]);

$title = "Settings Dashboard - " . SITE_NAME;
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3>Settings Dashboard</h3>
            </div>
            <div class="card-body">
                <h4>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h4>
                <p>This is the settings dashboard where you can manage system-wide configuration options.</p>

                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card bg-dark text-light h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">General Settings</h5>
                                <p class="card-text flex-grow-1">Configure general application settings.</p>
                                <a href="<?php echo getRelativePath('admin/settings/general.php'); ?>" class="btn btn-primary mt-auto">Manage</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-dark text-light h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">Email Settings</h5>
                                <p class="card-text flex-grow-1">Configure email server and templates.</p>
                                <a href="<?php echo getRelativePath('admin/settings/email.php'); ?>" class="btn btn-primary mt-auto">Manage</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-dark text-light h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">Security Settings</h5>
                                <p class="card-text flex-grow-1">Configure security policies and options.</p>
                                <a href="<?php echo getRelativePath('admin/settings/security.php'); ?>" class="btn btn-primary mt-auto">Manage</a>
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
include '../../includes/layout.php';
?>
