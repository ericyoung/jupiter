<?php
require_once '../includes/functions.php';

// Ensure the user doesn't have valid session data at this point
// The user-related data was already unset in the logout function
// The flash message should be available and will be displayed by the layout

$title = "Logged Out - " . SITE_NAME;
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center">Logged Out</h3>
            </div>
            <div class="card-body text-center">
                <div class="alert alert-success">
                    <h4 class="alert-heading">Successfully Logged Out!</h4>
                    <p>You have been successfully logged out of your account.</p>
                </div>
                <a href="<?php echo getRelativePath('index.php'); ?>" class="btn btn-primary">Return to Home</a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>
