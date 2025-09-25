<?php
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

// Check if user has client role (client or client_admin)
if (!isClient()) {
    // If user is not a client, redirect to admin dashboard
    header('Location: ../admin/dashboard.php');
    exit;
}

$title = "Client Dashboard - " . SITE_NAME;
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3>Client Dashboard</h3>
            </div>
            <div class="card-body">
                <h4>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h4>
                <p>This is your client dashboard. Here you can manage your account and access client-specific features.</p>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-dark text-light">
                            <div class="card-body">
                                <h5 class="card-title">Account Info</h5>
                                <p class="card-text">Manage your account settings and profile.</p>
                                <a href="<?php echo getRelativePath('clients/profile.php'); ?>" class="btn btn-primary">Manage Account</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-dark text-light">
                            <div class="card-body">
                                <h5 class="card-title">Projects</h5>
                                <p class="card-text">View and manage your projects.</p>
                                <a href="<?php echo getRelativePath('#'); ?>" class="btn btn-primary">View Projects</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-dark text-light">
                            <div class="card-body">
                                <h5 class="card-title">Reports</h5>
                                <p class="card-text">Generate and view reports.</p>
                                <a href="<?php echo getRelativePath('#'); ?>" class="btn btn-primary">View Reports</a>
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