<?php
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

// Check if user has admin role (not a client role)
if (isClient()) {
    // If user has a client role, redirect to client dashboard
    header('Location: ../clients/dashboard.php');
    exit;
}

// Check if user has permission to view audit logs (superadmin, executive, or accounts)
$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['superadmin', 'executive', 'accounts'];
if (!in_array($userRole, $allowedRoles)) {
    setFlash('error', 'You do not have permission to view audit logs.');
    header('Location: ../admin/dashboard.php');
    exit;
}

// Get search and filter parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

// Validation: Ensure dates are in correct format
if ($startDate && !strtotime($startDate)) {
    $startDate = '';
}
if ($endDate && !strtotime($endDate)) {
    $endDate = '';
}

$limit = 10; // Records per page
$offset = ($page - 1) * $limit;

// Get audit logs with filtering and pagination
global $pdo;

// Build the query with optional date filtering
$whereClause = "1=1";
$params = [];

if (!empty($startDate)) {
    $whereClause .= " AND DATE(a.created_at) >= ?";
    $params[] = $startDate;
}
if (!empty($endDate)) {
    $whereClause .= " AND DATE(a.created_at) <= ?";
    $params[] = $endDate;
}

// Get audit logs with filtering
$stmt = $pdo->prepare("SELECT a.*, u.name as username FROM audits a LEFT JOIN users u ON a.user_id = u.id WHERE $whereClause ORDER BY a.created_at DESC LIMIT ? OFFSET ?");
$paramsWithLimit = array_merge($params, [$limit, $offset]);
$stmt->execute($paramsWithLimit);
$audits = $stmt->fetchAll();

// Get total count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM audits a WHERE $whereClause");
$countStmt->execute($params);
$totalCount = $countStmt->fetch()['total'];
$totalPages = ceil($totalCount / $limit);

$title = "Audit Logs - " . SITE_NAME;
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3>Audit Logs</h3>
                <?php echo generateBreadcrumbs(); ?>
            </div>
            <div class="card-body">
                <h4>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h4>
                <p>All user actions are logged for security and compliance purposes.</p>
                
                <!-- Search and Filter Form -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <form method="GET">
                            <div class="row">
                                <div class="col-md-5">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                                </div>
                                <div class="col-md-5">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>IP Address</th>
                                <th>Date/Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($audits)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No audit logs found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($audits as $audit): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($audit['id']); ?></td>
                                    <td>
                                        <?php if ($audit['username']): ?>
                                            <?php echo htmlspecialchars($audit['username']); ?> (ID: <?php echo $audit['user_id']; ?>)
                                        <?php else: ?>
                                            User ID: <?php echo $audit['user_id']; ?> (deleted)
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-primary"><?php echo htmlspecialchars($audit['action']); ?></span></td>
                                    <td><?php echo htmlspecialchars($audit['description'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($audit['ip_address'] ?? 'Unknown'); ?></td>
                                    <td><?php echo htmlspecialchars($audit['created_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Audit logs pagination">
                    <ul class="pagination justify-content-center">
                        <?php 
                        // Calculate pagination range to show
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        ?>
                        
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page - 1); ?><?php echo $startDate ? '&start_date=' . urlencode($startDate) : ''; ?><?php echo $endDate ? '&end_date=' . urlencode($endDate) : ''; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if ($startPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1<?php echo $startDate ? '&start_date=' . urlencode($startDate) : ''; ?><?php echo $endDate ? '&end_date=' . urlencode($endDate) : ''; ?>">1</a>
                            </li>
                            <?php if ($startPage > 2): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $startDate ? '&start_date=' . urlencode($startDate) : ''; ?><?php echo $endDate ? '&end_date=' . urlencode($endDate) : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo $startDate ? '&start_date=' . urlencode($startDate) : ''; ?><?php echo $endDate ? '&end_date=' . urlencode($endDate) : ''; ?>"><?php echo $totalPages; ?></a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page + 1); ?><?php echo $startDate ? '&start_date=' . urlencode($startDate) : ''; ?><?php echo $endDate ? '&end_date=' . urlencode($endDate) : ''; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
                
                <div class="mt-3">
                    <p>Total records: <?php echo $totalCount; ?> 
                    <?php if ($startDate || $endDate): ?>
                        (filtered from <?php echo date('Y-m-d', strtotime($startDate ?: 'now')); ?> to <?php echo date('Y-m-d', strtotime($endDate ?: 'now')); ?>)
                    <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>