<?php
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit;
}

// Check if user is a superadmin
if (!isSuperAdmin()) {
    setFlash('error', 'Only superadmins can manage roles.');
    header('Location: ../dashboard.php');
    exit;
}

setCustomBreadcrumbs([
    ['name' => 'Dashboard', 'url' => getRelativePath('admin/dashboard.php')],
    ['name' => 'Roles', 'url' => '']
]);

// Get all roles
$roles = getAllRoles();

$title = "Manage Roles - " . SITE_NAME;
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h3>Manage Roles</h3>
                </div>
                <a href="create.php" class="btn btn-primary">Create New Role</a>
            </div>
            <div class="card-body">
                <p>Manage application roles and their hierarchy. Drag and drop roles to adjust the hierarchy level.</p>

                <div class="table-responsive">
                    <table class="table table-dark">
                        <thead>
                            <tr>
                                <th>Display Name</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Hierarchy Level</th>
                                <th>Description</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="roles-list">
                            <?php foreach ($roles as $role): ?>
                            <tr data-role-id="<?php echo $role['id']; ?>" data-hierarchy-level="<?php echo $role['hierarchy_level']; ?>">
                                <td>
                                    <i class="fas fa-bars me-2" style="cursor: move;"></i>
                                    <?php echo htmlspecialchars($role['display_name']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($role['name']); ?></td>
                                <td>
                                    <?php if ($role['is_client_role']): ?>
                                        <span class="badge bg-secondary">Client</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">Admin</span>
                                    <?php endif; ?>
                                </td>
                                <td class="hierarchy-value"><?php echo $role['hierarchy_level']; ?></td>
                                <td><?php echo htmlspecialchars($role['description'] ?? ''); ?></td>
                                <td><?php echo date('M j, Y', strtotime($role['created_at'])); ?></td>
                                <td>
                                    <a href="edit.php?id=<?php echo $role['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                    <?php if ($role['name'] !== 'superadmin'): ?>
                                        <a href="delete.php?id=<?php echo $role['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this role?')">Delete</a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-danger disabled" title="Cannot delete superadmin role">Delete</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info mt-3">
                    <strong>Role Hierarchy Guide:</strong> Higher numbers have more privileges. Superadmin (100) has the highest privileges,
                    Clients have the lowest. Drag roles to adjust their hierarchy.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include SortableJS for drag and drop functionality -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize drag and drop for role hierarchy
    const rolesList = document.getElementById('roles-list');

    if (rolesList) {
        const sortable = new Sortable(rolesList, {
            animation: 150,
            ghostClass: 'bg-secondary',
            onEnd: function(evt) {
                // Update hierarchy levels after drag and drop
                updateHierarchyLevels();
            }
        });
    }

    function updateHierarchyLevels() {
        const rows = rolesList.querySelectorAll('tr');
        const roleUpdates = [];

        rows.forEach(function(row, index) {
            const roleId = row.getAttribute('data-role-id');
            const newLevel = 100 - (index * 5); // Start from 100 and decrease by 5 for each position

            // Update the hierarchy value display
            const hierarchyCell = row.querySelector('.hierarchy-value');
            hierarchyCell.textContent = newLevel;

            // Store for API update
            roleUpdates.push({
                id: roleId,
                hierarchy_level: newLevel
            });
        });

        // Send AJAX request to update hierarchy in database
        if (roleUpdates.length > 0) {
            fetch('update_hierarchy.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ roles: roleUpdates })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showMessage('Hierarchy updated successfully!', 'success');
                } else {
                    showMessage('Error updating hierarchy: ' + (data.message || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                showMessage('Error updating hierarchy: ' + error.message, 'error');
            });
        }
    }

    function showMessage(message, type) {
        // Create a temporary alert message
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-' + (type === 'error' ? 'danger' : 'success') + ' alert-dismissible fade show position-fixed top-0 end-0 m-3';
        alertDiv.style.zIndex = '9999';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

        document.body.appendChild(alertDiv);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv);
            }
        }, 5000);
    }
});
</script>

<?php
$content = ob_get_clean();
include '../../includes/layout.php';
?>
