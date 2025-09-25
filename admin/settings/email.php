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
    ['name' => 'Email', 'url' => '']
]);

// Check if user has permission to manage settings (superadmin, executive, or accounts)
$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['superadmin', 'executive', 'accounts'];
if (!in_array($userRole, $allowedRoles)) {
    setFlash('error', 'You do not have permission to manage settings.');
    header('Location: ../dashboard.php');
    exit;
}

$title = "Email Settings - " . SITE_NAME;
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3>Email Settings</h3>
            </div>
            <div class="card-body">
                <h4>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h4>
                <p>Configure email server and templates.</p>

                <form method="POST">
                    <div class="mb-3">
                        <label for="smtpHost" class="form-label">SMTP Host</label>
                        <input type="text" class="form-control" id="smtpHost" name="smtp_host" value="smtp.gmail.com">
                    </div>

                    <div class="mb-3">
                        <label for="smtpPort" class="form-label">SMTP Port</label>
                        <input type="number" class="form-control" id="smtpPort" name="smtp_port" value="587">
                    </div>

                    <div class="mb-3">
                        <label for="smtpUser" class="form-label">SMTP Username</label>
                        <input type="text" class="form-control" id="smtpUser" name="smtp_user" value="">
                    </div>

                    <div class="mb-3">
                        <label for="smtpPass" class="form-label">SMTP Password</label>
                        <input type="password" class="form-control" id="smtpPass" name="smtp_pass" value="">
                    </div>

                    <div class="mb-3">
                        <label for="emailFrom" class="form-label">From Email</label>
                        <input type="email" class="form-control" id="emailFrom" name="email_from" value="noreply@example.com">
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
