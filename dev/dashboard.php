<?php
require_once '../includes/functions.php';

// This page should only be accessible in development environment
if (ENVIRONMENT !== 'development') {
    // In production, redirect to home
    header('Location: ../index.php');
    exit;
}

setCustomBreadcrumbs([
    ['name' => 'Dashboard', 'url' => getRelativePath('admin/dashboard.php')],
    ['name' => 'Developer', 'url' => '']
]);

$title = "Developer Dashboard - " . SITE_NAME;
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3>Developer Dashboard</h3>
            </div>
            <div class="card-body">
                <h4>Welcome, Developer!</h4>
                <p>This is the development environment dashboard. Here you can access development tools.</p>

                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-dark text-light">
                            <div class="card-body">
                                <h5 class="card-title">Emails</h5>
                                <p class="card-text">View emails that would have been sent in production.</p>
                                <a href="emails.php" class="btn btn-primary">View Emails</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-dark text-light">
                            <div class="card-body">
                                <h5 class="card-title">Database Tools</h5>
                                <p class="card-text">Access database utilities for development.</p>
                                <a href="#" class="btn btn-primary disabled">Coming Soon</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-dark text-light">
                            <div class="card-body">
                                <h5 class="card-title">API Testing</h5>
                                <p class="card-text">Test API endpoints in development.</p>
                                <a href="#" class="btn btn-primary disabled">Coming Soon</a>
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
