<?php
require_once '../includes/functions.php';

// Check if user is already logged in and redirect accordingly
checkAlreadyLoggedIn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        setFlash('error', 'Invalid request. Please try again.');
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        
        // Validation
        if (empty($email)) {
            setFlash('error', 'Please enter your email address.');
        } elseif (!validateEmail($email)) {
            setFlash('error', 'Please enter a valid email address.');
        } else {
            // Check if email exists in database
            $user = getUserByEmail($email);
            if ($user) {
                // Generate and save reset token
                $token = generateResetToken($email);
                if ($token) {
                    // In a real application, you would send an email here
                    // For now, we'll just show the token for testing purposes
                    setFlash('success', 'Password reset instructions have been sent to your email. (For demo purposes: token is ' . $token . ')');
                } else {
                    setFlash('error', 'An error occurred. Please try again.');
                }
            } else {
                // To avoid email enumeration, show the same message
                setFlash('success', 'If your email exists in our system, you will receive a password reset link.');
            }
        }
    }
}

$csrf_token = generateCSRFToken();

$title = "Forgot Password - " . SITE_NAME;
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center">Forgot Password</h3>
            </div>
            <div class="card-body">
                <p class="text-center">Enter your email and we'll send you a link to reset your password.</p>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Send Reset Link</button>
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