<?php
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit;
}

// Check if user is a superadmin
if (!isSuperAdmin()) {
    setFlash('error', 'Only superadmins can create roles.');
    header('Location: ../dashboard.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        setFlash('error', 'Invalid request. Please try again.');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    $name = sanitizeInput($_POST['name'] ?? '');
    $displayName = sanitizeInput($_POST['display_name'] ?? '');
    $isClientRole = isset($_POST['is_client_role']) ? 1 : 0;
    $hierarchyLevel = (int)($_POST['hierarchy_level'] ?? 0);
    $description = sanitizeInput($_POST['description'] ?? '');
    
    // Validation
    $errors = [];
    if (empty($name)) {
        $errors[] = 'Role name is required.';
    } elseif (!preg_match('/^[a-z0-9_]+$/', $name)) {
        $errors[] = 'Role name can only contain lowercase letters, numbers, and underscores.';
    }
    if (empty($displayName)) {
        $errors[] = 'Display name is required.';
    }
    if ($hierarchyLevel < 0 || $hierarchyLevel > 100) {
        $errors[] = 'Hierarchy level must be between 0 and 100.';
    }
    
    // Check if role name already exists
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        $errors[] = 'Role name already exists.';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO roles (name, display_name, is_client_role, hierarchy_level, description) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([$name, $displayName, $isClientRole, $hierarchyLevel, $description]);
            
            if ($result) {
                setFlash('success', 'Role created successfully!');
                header('Location: list.php');
                exit;
            } else {
                $errors[] = 'Failed to create role.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        setFlash('error', implode('<br>', $errors));
    }
}

$csrf_token = generateCSRFToken();

$title = "Create Role - " . SITE_NAME;
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3>Create New Role</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Role Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required 
                               placeholder="e.g., project_manager">
                        <div class="form-text">Use lowercase letters, numbers, and underscores only (e.g., project_manager)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="display_name" class="form-label">Display Name</label>
                        <input type="text" class="form-control" id="display_name" name="display_name" value="<?php echo htmlspecialchars($_POST['display_name'] ?? ''); ?>" required
                               placeholder="e.g., Project Manager">
                        <div class="form-text">Name shown to users in the interface</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="hierarchy_level" class="form-label">Hierarchy Level</label>
                        <input type="number" class="form-control" id="hierarchy_level" name="hierarchy_level" min="0" max="100" 
                               value="<?php echo htmlspecialchars($_POST['hierarchy_level'] ?? '50'); ?>" required>
                        <div class="form-text">Higher numbers have more privileges. Superadmin is 100, Client is 10.</div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_client_role" name="is_client_role" value="1" 
                                   <?php echo (isset($_POST['is_client_role']) && $_POST['is_client_role']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_client_role">This is a client role</label>
                        </div>
                        <div class="form-text">Client roles have limited permissions compared to admin roles.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  placeholder="Describe what this role can do"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="list.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create Role</button>
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