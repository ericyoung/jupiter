<?php
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit;
}

// Check if user is a superadmin
if (!isSuperAdmin()) {
    setFlash('error', 'Only superadmins can edit roles.');
    header('Location: ../dashboard.php');
    exit;
}

// Get role ID from query parameter
$roleId = $_GET['id'] ?? '';
if (empty($roleId)) {
    setFlash('error', 'No role ID provided.');
    header('Location: list.php');
    exit;
}

// Get role data
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM roles WHERE id = ?");
$stmt->execute([$roleId]);
$role = $stmt->fetch();

if (!$role) {
    setFlash('error', 'Role not found.');
    header('Location: list.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        setFlash('error', 'Invalid request. Please try again.');
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . urlencode($roleId));
        exit;
    }
    
    $displayName = sanitizeInput($_POST['display_name'] ?? '');
    $isClientRole = isset($_POST['is_client_role']) ? 1 : 0;
    $hierarchyLevel = (int)($_POST['hierarchy_level'] ?? 0);
    $description = sanitizeInput($_POST['description'] ?? '');
    
    // Validation
    $errors = [];
    if (empty($displayName)) {
        $errors[] = 'Display name is required.';
    }
    if ($hierarchyLevel < 0 || $hierarchyLevel > 100) {
        $errors[] = 'Hierarchy level must be between 0 and 100.';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE roles SET display_name = ?, is_client_role = ?, hierarchy_level = ?, description = ? WHERE id = ?");
            $result = $stmt->execute([$displayName, $isClientRole, $hierarchyLevel, $description, $roleId]);
            
            if ($result) {
                // Log the role update
                $currentUserId = $_SESSION['user_id'];
                logAudit($currentUserId, 'role_update', 'Updated role ID ' . $roleId . ' (' . $displayName . ')');
                
                setFlash('success', 'Role updated successfully!');
                header('Location: list.php');
                exit;
            } else {
                $errors[] = 'Failed to update role.';
                // Log the failure
                $currentUserId = $_SESSION['user_id'];
                logAudit($currentUserId, 'role_update_failed', 'Failed to update role ID ' . $roleId . ': Database error');
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        setFlash('error', implode('<br>', $errors));
    }
    
    // Refresh role data if there were validation errors
    $stmt = $pdo->prepare("SELECT * FROM roles WHERE id = ?");
    $stmt->execute([$roleId]);
    $role = $stmt->fetch();
}

// If form was submitted with errors, use POST data; otherwise use DB data
$displayName = htmlspecialchars($_POST['display_name'] ?? $role['display_name']);
$isClientRole = isset($_POST['is_client_role']) ? 1 : (int)$role['is_client_role'];
$hierarchyLevel = $_POST['hierarchy_level'] ?? $role['hierarchy_level'];
$description = htmlspecialchars($_POST['description'] ?? $role['description']);

$csrf_token = generateCSRFToken();

$title = "Edit Role - " . SITE_NAME;
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3>Edit Role: <?php echo htmlspecialchars($role['display_name']); ?></h3>
                <?php echo generateBreadcrumbs(); ?>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Role Name</label>
                        <input type="text" class="form-control" id="name" value="<?php echo htmlspecialchars($role['name']); ?>" disabled>
                        <div class="form-text">Role names cannot be changed after creation</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="display_name" class="form-label">Display Name</label>
                        <input type="text" class="form-control" id="display_name" name="display_name" value="<?php echo $displayName; ?>" required
                               placeholder="e.g., Project Manager">
                        <div class="form-text">Name shown to users in the interface</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="hierarchy_level" class="form-label">Hierarchy Level</label>
                        <input type="number" class="form-control" id="hierarchy_level" name="hierarchy_level" min="0" max="100" 
                               value="<?php echo $hierarchyLevel; ?>" required>
                        <div class="form-text">Higher numbers have more privileges. Superadmin is 100, Client is 10.</div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_client_role" name="is_client_role" value="1" 
                                   <?php echo $isClientRole ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_client_role">This is a client role</label>
                        </div>
                        <div class="form-text">Client roles have limited permissions compared to admin roles.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  placeholder="Describe what this role can do"><?php echo $description; ?></textarea>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="list.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Role</button>
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