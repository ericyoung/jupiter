<?php
require_once 'includes/functions.php';

// Check if user is already logged in and redirect accordingly
checkAlreadyLoggedIn();

$title = "Home - " . SITE_NAME;
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center">Welcome to <?php echo SITE_NAME; ?></h3>
            </div>
            <div class="card-body">
                <p class="card-text">This is a secure application with role-based access control.</p>
                <div class="text-center">
                    <a href="auth/login.php" class="btn btn-primary me-2">Login</a>
                    <a href="auth/register.php" class="btn btn-outline-primary">Register</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>