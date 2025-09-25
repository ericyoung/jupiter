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
    ['name' => 'Security', 'url' => '']
]);

// Check if user has permission to manage settings (superadmin, executive, or accounts)
$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['superadmin', 'executive', 'accounts'];
if (!in_array($userRole, $allowedRoles)) {
    setFlash('error', 'You do not have permission to manage settings.');
    header('Location: ../dashboard.php');
    exit;
}

$title = "Security Settings - " . SITE_NAME;
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3>Security Settings</h3>
            </div>
            <div class="card-body">
                <h4>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h4>
                <p>Configure security policies and options.</p>

                <form method="POST">
                    <div class="mb-3">
                        <label for="sessionLifetime" class="form-label">Session Lifetime (minutes)</label>
                        <input type="number" class="form-control" id="sessionLifetime" name="session_lifetime" value="60">
                        <div class="form-text">How long a user session remains active</div>
                    </div>

                    <div class="mb-3">
                        <label for="passwordMinLength" class="form-label">Minimum Password Length</label>
                        <input type="number" class="form-control" id="passwordMinLength" name="password_min_length" value="8">
                        <div class="form-text">Minimum number of characters required for passwords</div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="require2fa" name="require_2fa">
                        <label class="form-check-label" for="require2fa">Require Two-Factor Authentication</label>
                        <div class="form-text">Force users to enable 2FA for enhanced security</div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="forceHttps" name="force_https">
                        <label class="form-check-label" for="forceHttps">Force HTTPS Connections</label>
                        <div class="form-text">Redirect all HTTP traffic to HTTPS</div>
                    </div>

                    <div class="mb-3">
                        <label for="maxLoginAttempts" class="form-label">Max Login Attempts</label>
                        <input type="number" class="form-control" id="maxLoginAttempts" name="max_login_attempts" value="5">
                        <div class="form-text">Number of failed login attempts before lockout</div>
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
