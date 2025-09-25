<?php
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

// Initialize variables
$message = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        setFlash('error', 'Invalid request. Please try again.');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    $name = sanitizeInput($_POST['name'] ?? '');
    
    // Validation
    if (empty($name)) {
        setFlash('error', 'Name is required.');
    } else {
        try {
            global $pdo;
            $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
            $result = $stmt->execute([$name, $_SESSION['user_id']]);
            
            if ($result) {
                // Update session name
                $_SESSION['name'] = $name;
                setFlash('success', 'Profile updated successfully!');
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } else {
                setFlash('error', 'Failed to update profile. Please try again.');
            }
        } catch (PDOException $e) {
            setFlash('error', 'An error occurred. Please try again.');
        }
    }
}

$csrf_token = generateCSRFToken();

// Get current user data with role information
global $pdo;
$stmt = $pdo->prepare("SELECT u.name, u.email, r.name as role, r.display_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('error', 'User not found.');
    redirectToDashboard();
    exit;
}

$title = "Edit Profile - " . SITE_NAME;
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3>Edit Profile</h3>
                <?php echo generateBreadcrumbs(); ?>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        <div class="form-text">Email cannot be changed from this page</div>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <input type="text" class="form-control" id="role" value="<?php echo htmlspecialchars($user['role']); ?>" disabled>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </div>
                </form>
                
                <div class="mt-3">
                    <a href="<?php echo getRelativePath('clients/dashboard.php'); ?>" class="btn btn-secondary">Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>