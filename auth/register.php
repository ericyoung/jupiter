<?php
require_once '../includes/functions.php';

// Check if user is already logged in and redirect accordingly
checkAlreadyLoggedIn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        setFlash('error', 'Invalid request. Please try again.');
    } else {
        $name = sanitizeInput($_POST['name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
            setFlash('error', 'Please fill in all fields.');
        } elseif (!validateEmail($email)) {
            setFlash('error', 'Please enter a valid email address.');
        } elseif ($password !== $confirmPassword) {
            setFlash('error', 'Passwords do not match.');
        } elseif (strlen($password) < 6) {
            setFlash('error', 'Password must be at least 6 characters long.');
        } elseif (emailExists($email)) {
            // Check if the account exists but is not active
            $existingUser = getUserByEmail($email);
            if ($existingUser && !$existingUser['is_active']) {
                // User exists but is not confirmed, send confirmation again
                $token = generateToken();

                global $pdo;
                $stmt = $pdo->prepare("UPDATE users SET activation_token = ? WHERE email = ?");
                $result = $stmt->execute([$token, $email]);

                if ($result) {
                    sendConfirmationEmail($email, $existingUser['name'], $token);
                    setFlash('success', 'Account already exists but not confirmed. A new confirmation email has been sent to your email address.');
                } else {
                    setFlash('error', 'An account with this email already exists and is not confirmed. Please contact support if you continue to have issues.');
                }
            } else {
                setFlash('error', 'An account with this email already exists.');
            }
        } else {
            if (registerUser($name, $email, $password)) {
                setFlash('success', 'Registration successful! Please check your email to confirm your account before logging in.');
            } else {
                setFlash('error', 'Registration failed. Please try again.');
            }
        }
    }
}

$csrf_token = generateCSRFToken();

$title = "Register - " . SITE_NAME;
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center">Register</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Register</button>
                    </div>
                </form>

                <div class="text-center mt-3">
                    <p>Already have an account? <a href="<?php echo getRelativePath('auth/login.php'); ?>">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>
