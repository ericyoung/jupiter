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

setCustomBreadcrumbs([
    ['name' => 'Dashboard', 'url' => getRelativePath('admin/dashboard.php')],
    ['name' => 'Users', 'url' => '']
]);

// Check if user has permission to manage users (superadmin, executive, or accounts)
$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['superadmin', 'executive', 'accounts'];
if (!in_array($userRole, $allowedRoles)) {
    setFlash('error', 'You do not have permission to manage users.');
    header('Location: ../dashboard.php');
    exit;
}

// Get all users from the database with role and company information
global $pdo;
$stmt = $pdo->query("SELECT u.id, u.name, u.email, u.role_id, r.name as role, r.display_name, u.company_id, c.company_name, u.is_active, u.created_at FROM users u LEFT JOIN roles r ON u.role_id = r.id LEFT JOIN companies c ON u.company_id = c.id ORDER BY u.created_at DESC");
$users = $stmt->fetchAll();

$title = "Manage Users - " . SITE_NAME;
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h3>Manage Users</h3>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($users)): ?>
                    <div class="alert alert-info">No users found.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Company</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo in_array($user['role'], ['superadmin', 'executive', 'accounts']) ? 'primary' : 'secondary'; ?>">
                                        <?php echo htmlspecialchars($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($user['company_name'])): ?>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($user['company_name']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">None</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                <td>
                                    <?php
                                    // Check if current user has permission to edit this user based on hierarchy
                                    $currentUserRole = $_SESSION['role'] ?? '';

                                    // Get current user's role details to compare hierarchy
                                    global $pdo;
                                    $currentRoleStmt = $pdo->prepare("SELECT r.hierarchy_level FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
                                    $currentRoleStmt->execute([$_SESSION['user_id']]);
                                    $currentRoleDetails = $currentRoleStmt->fetch();

                                    $currentUserHierarchy = $currentRoleDetails['hierarchy_level'] ?? 0;

                                    // Get the user being edited's hierarchy level
                                    $targetUserHierarchy = $user['hierarchy_level'] ?? 0;

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

                                    if ($canEdit):
                                    ?>
                                        <a href="edit.php?id=<?php echo urlencode($user['id']); ?>" class="btn btn-sm btn-primary">Edit</a>
                                    <?php else: ?>
                                        <span class="text-muted">No permission</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../includes/layout.php';
?>
