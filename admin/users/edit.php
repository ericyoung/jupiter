<?php
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit;
}

// Check if user has admin access (not a client role)
if (isClient()) {
    header('Location: ../../clients/dashboard.php');
    exit;
}

// Check if user has permission to manage users (superadmin, executive, or accounts)
$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['superadmin', 'executive', 'accounts'];
if (!in_array($userRole, $allowedRoles)) {
    setFlash('error', 'You do not have permission to manage users.');
    header('Location: ../dashboard.php');
    exit;
}

// Get user ID from query parameter
$userId = $_GET['id'] ?? '';
if (empty($userId)) {
    setFlash('error', 'No user ID provided.');
    header('Location: list.php');
    exit;
}

// Get user data along with role information
global $pdo;
$stmt = $pdo->prepare("SELECT u.id, u.name, u.email, u.role_id, u.is_active, r.name as role FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('error', 'User not found.');
    header('Location: list.php');
    exit;
}

// Check if current user has permission to edit this user based on hierarchy
global $pdo;

// Get current user's role details to compare hierarchy
$currentRoleStmt = $pdo->prepare("SELECT r.hierarchy_level FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
$currentRoleStmt->execute([$_SESSION['user_id']]);
$currentRoleDetails = $currentRoleStmt->fetch();

$currentUserHierarchy = $currentRoleDetails['hierarchy_level'] ?? 0;
$targetUserHierarchy = $user['hierarchy_level'] ?? 0;

$currentUserRole = $_SESSION['role'] ?? '';

$canEdit = false;

// Superadmin special case: can edit anyone except other superadmins (unless it's themselves)
if ($currentUserRole === 'superadmin') {
    if ($user['role'] === 'superadmin' && $user['id'] != $_SESSION['user_id']) {
        $canEdit = false; // Cannot edit other superadmins
    } else {
        $canEdit = true; // Can edit everyone else
    }
} else {
    // For non-superadmins: can only edit users with hierarchy at or below their own
    $canEdit = $currentUserHierarchy >= $targetUserHierarchy;
}

if (!$canEdit) {
    setFlash('error', 'You do not have permission to edit this user.');
    header('Location: list.php');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        setFlash('error', 'Invalid request. Please try again.');
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . urlencode($userId));
        exit;
    }

    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $roleName = $_POST['role'] ?? '';
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // Validation
    $errors = [];
    if (empty($name)) {
        $errors[] = 'Name is required.';
    }
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!validateEmail($email)) {
        $errors[] = 'Please enter a valid email address.';
    }
    // Check if email already exists for another user
    $existingUser = getUserByEmail($email);
    if ($existingUser && $existingUser['id'] != $userId) {
        $errors[] = 'Email already exists.';
    }

    // Get role details for the role name
    $roleStmt = $pdo->prepare("SELECT id, is_client_role, hierarchy_level FROM roles WHERE name = ?");
    $roleStmt->execute([$roleName]);
    $roleData = $roleStmt->fetch();

    if (!$roleData) {
        $errors[] = 'Invalid role selected.';
    } else {
        $roleId = $roleData['id'];
        $targetHierarchy = $roleData['hierarchy_level'];
        
        // Check if current user has permission to assign this role based on hierarchy
        // Get current user's role details
        $currentRoleStmt = $pdo->prepare("SELECT r.hierarchy_level FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
        $currentRoleStmt->execute([$_SESSION['user_id']]);
        $currentRoleDetails = $currentRoleStmt->fetch();
        $currentUserHierarchy = $currentRoleDetails['hierarchy_level'] ?? 0;
        
        // Superadmin special case: can assign any role except to other superadmins (unless editing own profile)
        $currentUserRole = $_SESSION['role'] ?? '';
        if ($currentUserRole === 'superadmin' && !($user['role'] === 'superadmin' && $userId != $_SESSION['user_id'])) {
            // Superadmin can assign any role
        } else {
            // For non-superadmins: can only assign roles with hierarchy at or below their own
            if ($currentUserHierarchy < $targetHierarchy) {
                $errors[] = 'You cannot assign roles with higher hierarchy than your own.';
            }
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role_id = ?, is_active = ? WHERE id = ?");
            $result = $stmt->execute([$name, $email, $roleId, $isActive, $userId]);

            if ($result) {
                    // If the current user is editing their own profile, update session data
                    if ($userId == $_SESSION['user_id']) {
                        $_SESSION['name'] = $name; // Update session name to reflect the change
                    }
                    
                    // Log the user update
                    $currentUserId = $_SESSION['user_id'];
                    logAudit($currentUserId, 'user_update', 'Updated user ID ' . $userId . ' (' . $name . ')');
                    
                    setFlash('success', 'User updated successfully!');
                    // Redirect to prevent resubmission on refresh
                    header('Location: list.php');
                    exit;
                } else {
                    $errors[] = 'Failed to update user.';
                    // Log the failure
                    $currentUserId = $_SESSION['user_id'];
                    logAudit($currentUserId, 'user_update_failed', 'Failed to update user ID ' . $userId . ': Database error');
                }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }

    // If there are errors, set them as flash messages
    if (!empty($errors)) {
        setFlash('error', implode('<br>', $errors));
    }
}

// Get current user data (in case of form resubmission with errors)
$stmt = $pdo->prepare("SELECT u.id, u.name, u.email, u.role_id, u.is_active, r.name as role FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$csrf_token = generateCSRFToken();

$title = "Edit User - " . SITE_NAME;
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3>Edit User</h3>
                <?php echo generateBreadcrumbs(); ?>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-control" id="role" name="role" required>
                            <?php
                            // Get all roles for selection
                            $rolesStmt = $pdo->query("SELECT id, name, display_name, is_client_role FROM roles ORDER BY hierarchy_level DESC");
                            $roles = $rolesStmt->fetchAll();
                            foreach ($roles as $role_option):
                            ?>
                                <option value="<?php echo htmlspecialchars($role_option['name']); ?>"
                                    <?php echo $user['role'] === $role_option['name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($role_option['display_name']); ?> 
                                    <?php if ($role_option['is_client_role']): ?>
                                        (Client Role)
                                    <?php else: ?>
                                        (Admin Role)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?php echo $user['is_active'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">Active Account</label>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="list.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update User</button>
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
