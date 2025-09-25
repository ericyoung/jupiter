<?php
// Simple Test Framework for Jupiter PHP Project
// Provides an interface to test various application pages and functionality

require_once 'includes/functions.php';

// Session is already started in functions.php

// Check if user is logged in for full functionality
$isLoggedIn = isLoggedIn();

$tests = [
    'home' => [
        'name' => 'Home Page',
        'path' => 'index.php',
        'description' => 'Test the main home page'
    ],
    'auth' => [
        'name' => 'Auth Pages',
        'path' => 'auth/',
        'description' => 'Test login, register, etc. pages'
    ],
    'login' => [
        'name' => 'Login Page',
        'path' => 'auth/login.php',
        'description' => 'Test user login functionality'
    ],
    'register' => [
        'name' => 'Register Page',
        'path' => 'auth/register.php',
        'description' => 'Test user registration functionality'
    ],
    'dashboard_client' => [
        'name' => 'Client Dashboard',
        'path' => 'clients/dashboard.php',
        'description' => 'Test client dashboard access'
    ],
    'dashboard_admin' => [
        'name' => 'Admin Dashboard',
        'path' => 'admin/dashboard.php',
        'description' => 'Test admin dashboard access'
    ],
    'profile' => [
        'name' => 'Edit Profile',
        'path' => $isLoggedIn && isClient() ? 'clients/profile.php' : 'admin/profile.php',
        'description' => 'Test profile editing functionality'
    ],
    'users' => [
        'name' => 'Manage Users',
        'path' => 'admin/users/list.php',
        'description' => 'Test user management (admin only)',
        'requires' => ['superadmin', 'executive', 'accounts']
    ],
    'roles' => [
        'name' => 'Manage Roles',
        'path' => 'admin/roles/list.php',
        'description' => 'Test role management (superadmin only)',
        'requires' => ['superadmin']
    ]
];

// Filter tests based on user permissions
$availableTests = [];
$userRole = $_SESSION['role'] ?? null;

foreach ($tests as $key => $test) {
    if (isset($test['requires'])) {
        if ($userRole && in_array($userRole, $test['requires'])) {
            $availableTests[$key] = $test;
        }
    } else {
        $availableTests[$key] = $test;
    }
}

$title = "Test Framework - " . SITE_NAME;
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3>Application Test Framework</h3>
            </div>
            <div class="card-body">
                <p>Welcome to the Jupiter PHP Project Test Framework. This provides an interface to test various application pages and functionality.</p>
                
                <h4>User Status</h4>
                <?php if ($isLoggedIn): ?>
                    <p class="text-success">Logged in as: <?php echo htmlspecialchars($_SESSION['name']); ?> (Role: <?php echo htmlspecialchars($_SESSION['role']); ?>)</p>
                    <a href="includes/functions.php?action=logout" class="btn btn-danger">Logout</a>
                <?php else: ?>
                    <p class="text-warning">Not logged in</p>
                <?php endif; ?>
                
                <h4 class="mt-4">Available Tests</h4>
                <div class="list-group">
                    <?php foreach ($availableTests as $key => $test): ?>
                        <a href="<?php echo $test['path']; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1"><?php echo htmlspecialchars($test['name']); ?></h5>
                                <small>Access</small>
                            </div>
                            <p class="mb-1"><?php echo htmlspecialchars($test['description']); ?></p>
                            <?php if (isset($test['requires'])): ?>
                                <small>Required role: <?php echo implode(', ', $test['requires']); ?></small>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                    
                    <?php if (empty($availableTests) && $isLoggedIn): ?>
                        <div class="list-group-item">
                            <p>No tests available for your role. Try logging in with a different account.</p>
                        </div>
                    <?php elseif (empty($availableTests) && !$isLoggedIn): ?>
                        <div class="list-group-item">
                            <p>Please log in to access more test options.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <h4 class="mt-4">Manual Test Steps</h4>
                <ol>
                    <li>Login with test credentials (use demo accounts or register new users)</li>
                    <li>Test navigation between pages</li>
                    <li>Verify role-based access controls</li>
                    <li>Test form submissions (registration, profile updates)</li>
                    <li>Test admin functions (if applicable)</li>
                    <li>Verify that restricted pages redirect appropriately</li>
                </ol>
                
                <h4 class="mt-4">Common Test Scenarios</h4>
                <div class="row">
                    <div class="col-md-6">
                        <h5>Client User Flow</h5>
                        <ul>
                            <li>Login as client user</li>
                            <li>Access client dashboard</li>
                            <li>Edit profile</li>
                            <li>Verify cannot access admin areas</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5>Admin User Flow</h5>
                        <ul>
                            <li>Login as admin user</li>
                            <li>Access admin dashboard</li>
                            <li>Manage users (if permissions allow)</li>
                            <li>Manage roles (superadmin only)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>