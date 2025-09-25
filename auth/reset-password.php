<?php
require_once '../includes/functions.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: forgot-password.php');
    exit;
}

// Verify the token
$user = verifyResetToken($token);

if (!$user) {
    setFlash('error', 'Invalid or expired token.');
    header('Location: forgot-password.php');
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        setFlash('error', 'Invalid request. Please try again.');
    } else {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($password) || empty($confirmPassword)) {
            setFlash('error', 'Please fill in all fields.');
        } elseif ($password !== $confirmPassword) {
            setFlash('error', 'Passwords do not match.');
        } elseif (strlen($password) < 6) {
            setFlash('error', 'Password must be at least 6 characters long.');
        } else {
            // Reset the password
            if (resetPassword($token, $password)) {
                setFlash('success', 'Your password has been reset successfully. You can now login.');
                // Redirect to login after successful password reset
                header('Location: login.php');
                exit;
            } else {
                setFlash('error', 'An error occurred. Please try again.');
            }
        }
    }
}

$csrf_token = generateCSRFToken();

$title = "Reset Password - " . SITE_NAME;
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center">Reset Password</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Reset Password</button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <p><a href="<?php echo getRelativePath('auth/login.php'); ?>">Back to Login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>