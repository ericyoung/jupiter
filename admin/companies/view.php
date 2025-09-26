<?php
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit;
}

// Check if user has admin access
if (isClient()) {
    header('Location: ../../clients/dashboard.php');
    exit;
}

// Check if user has permission to manage companies (superadmin, executive, or accounts)
$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['superadmin', 'executive', 'accounts'];
if (!in_array($userRole, $allowedRoles)) {
    setFlash('error', 'You do not have permission to manage companies.');
    header('Location: ../dashboard.php');
    exit;
}

// Get company ID from query parameter
$companyId = $_GET['id'] ?? '';
if (empty($companyId)) {
    setFlash('error', 'No company ID provided.');
    header('Location: list.php');
    exit;
}

// Get company data
global $pdo;
$stmt = $pdo->prepare("SELECT c.*, COUNT(u.id) as user_count FROM companies c LEFT JOIN users u ON c.id = u.company_id WHERE c.id = ? GROUP BY c.id");
$stmt->execute([$companyId]);
$company = $stmt->fetch();

if (!$company) {
    setFlash('error', 'Company not found.');
    header('Location: list.php');
    exit;
}

// Get users associated with this company
$stmt = $pdo->prepare("SELECT u.id, u.name, u.email, u.role, u.is_active, u.created_at FROM users u WHERE u.company_id = ? ORDER BY u.created_at DESC");
$stmt->execute([$companyId]);
$users = $stmt->fetchAll();

// Set breadcrumbs for this page
setCustomBreadcrumbs([
    ['name' => 'Dashboard', 'url' => getRelativePath('admin/dashboard.php')],
    ['name' => 'Companies', 'url' => getRelativePath('admin/companies/list.php')],
    ['name' => 'View Company', 'url' => ''], // Current page, not clickable
]);

$title = "View Company - " . SITE_NAME;
ob_start();
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3><?php echo htmlspecialchars($company['name']); ?></h3>
                <div>
                    <a href="edit.php?id=<?php echo urlencode($company['id']); ?>" class="btn btn-sm btn-primary">Edit</a>
                    <a href="delete.php?id=<?php echo urlencode($company['id']); ?>" class="btn btn-sm btn-danger" 
                       onclick="return confirm('Are you sure you want to delete this company?')">Delete</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Company Information</h5>
                        <dl class="row">
                            <dt class="col-sm-4">Status</dt>
                            <dd class="col-sm-8">
                                <?php if ($company['enabled']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Inactive</span>
                                <?php endif; ?>
                            </dd>
                            
                            <dt class="col-sm-4">Company Number</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($company['company_number']); ?></dd>
                            
                            <dt class="col-sm-4">Created</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($company['created_at']); ?></dd>
                            
                            <?php if (!empty($company['updated_at']) && $company['updated_at'] !== $company['created_at']): ?>
                            <dt class="col-sm-4">Last Updated</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($company['updated_at']); ?></dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                    
                    <div class="col-md-6">
                        <h5>Contact Information</h5>
                        <dl class="row">
                            <?php if (!empty($company['address1']) || !empty($company['city']) || !empty($company['state_or_province']) || !empty($company['zip'])): ?>
                            <dt class="col-sm-4">Address</dt>
                            <dd class="col-sm-8">
                                <?php 
                                $address_parts = array_filter([
                                    $company['address1'],
                                    $company['city'] . (!empty($company['state_or_province']) ? ', ' . $company['state_or_province'] : ''),
                                    $company['zip']
                                ]);
                                echo implode('<br>', $address_parts);
                                ?>
                            </dd>
                            <?php endif; ?>
                            
                            <?php if (!empty($company['address2'])): ?>
                            <dt class="col-sm-4">Address Line 2</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($company['address2']); ?></dd>
                            <?php endif; ?>
                            
                            <?php if (!empty($company['primary_phone'])): ?>
                            <dt class="col-sm-4">Primary Phone</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($company['primary_phone']); ?></dd>
                            <?php endif; ?>
                            
                            <?php if (!empty($company['contact_name'])): ?>
                            <dt class="col-sm-4">Contact Name</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($company['contact_name']); ?></dd>
                            <?php endif; ?>
                            
                            <?php if (!empty($company['contact_email'])): ?>
                            <dt class="col-sm-4">Contact Email</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($company['contact_email']); ?></dd>
                            <?php endif; ?>
                            
                            <?php if (!empty($company['contact_phone'])): ?>
                            <dt class="col-sm-4">Contact Phone</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($company['contact_phone']); ?></dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Company Statistics</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Associated Users:</span>
                    <span class="badge bg-primary"><?php echo $company['user_count']; ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span>Status:</span>
                    <span><?php echo $company['is_active'] ? 'Active' : 'Inactive'; ?></span>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <a href="edit.php?id=<?php echo urlencode($company['id']); ?>" class="btn btn-primary w-100 mb-2">Edit Company</a>
                <a href="../users/list.php?company_id=<?php echo urlencode($company['id']); ?>" class="btn btn-outline-primary w-100 mb-2">View Associated Users</a>
                <a href="delete.php?id=<?php echo urlencode($company['id']); ?>" class="btn btn-danger w-100" 
                   onclick="return confirm('Are you sure you want to delete this company?')">Delete Company</a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Associated Users (<?php echo $company['user_count']; ?>)</h5>
                <a href="../users/create.php?company_id=<?php echo urlencode($company['id']); ?>" class="btn btn-sm btn-primary">Add User</a>
            </div>
            <div class="card-body">
                <?php if (empty($users)): ?>
                    <div class="alert alert-info">No users are currently associated with this company.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo in_array($user['role'], ['superadmin', 'executive', 'accounts']) ? 'primary' : 'secondary'; ?>">
                                        <?php echo htmlspecialchars($user['role']); ?>
                                    </span>
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
                                    <a href="../users/edit.php?id=<?php echo urlencode($user['id']); ?>" class="btn btn-sm btn-primary">Edit</a>
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