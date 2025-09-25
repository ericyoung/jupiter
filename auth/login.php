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
        $password = $_POST['password'] ?? '';
        
        // Validation
        if (empty($email) || empty($password)) {
            setFlash('error', 'Please fill in all fields.');
        } elseif (!validateEmail($email)) {
            setFlash('error', 'Please enter a valid email address.');
        } else {
            if (loginUser($email, $password)) {
                redirectUser();
            } else {
                // Check if user exists but is not active
                $user = getUserByEmail($email);
                if ($user && !$user['is_active']) {
                    // User exists but is not active
                    setFlash('error', 'Please confirm your email address before logging in. <a href="resend-confirmation.php?email=' . urlencode($email) . '">Resend confirmation email</a>');
                } else {
                    setFlash('error', 'Invalid email or password.');
                }
            }
        }
    }
}

$csrf_token = generateCSRFToken();

$title = "Login - " . SITE_NAME;
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center">Login</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <p><a href="forgot-password.php">Forgot Password?</a></p>
                    <p>Don't have an account? <a href="<?php echo getRelativePath('auth/register.php'); ?>">Register here</a></p>
                    <p>Need to resend confirmation? <a href="register.php">Re-register with the same email</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>