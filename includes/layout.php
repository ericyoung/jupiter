<?php
// Security headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY'); // Or SAMEORIGIN if you need frames on same origin
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains' . (HTTPS_ONLY ? '; preload' : ''));
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo $title ?? SITE_NAME; ?></title>
    <!-- Bootstrap 5 CSS with Dark Theme -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #121212;
            color: #e0e0e0;
        }
        .card {
            background-color: #1e1e1e;
            color: #e0e0e0;
        }
        .form-control {
            background-color: #2d2d2d;
            color: #e0e0e0;
            border: 1px solid #444;
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .alert {
            border: none;
        }
        .navbar {
            background-color: #1a1a1a;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo getRelativePath('index.php'); ?>"><?php echo SITE_NAME; ?></a>

            <div class="navbar-nav ms-auto">
                <?php if (isLoggedIn()): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo htmlspecialchars($_SESSION['name']); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo isClient() ? getRelativePath('clients/profile.php') : getRelativePath('admin/profile.php'); ?>">Edit Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo getRelativePath('includes/functions.php?action=logout'); ?>">Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a class="nav-link" href="<?php echo getRelativePath('auth/login.php'); ?>">Login</a>
                    <a class="nav-link" href="<?php echo getRelativePath('auth/register.php'); ?>">Register</a>
                <?php endif; ?>
            <?php if (ENVIRONMENT === 'development'): ?>
                    <a class="nav-link" href="<?php echo getRelativePath('dev/dashboard.php'); ?>">Dev Tools</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php
        // Display GET-based messages (for backward compatibility)
        if (isset($_GET['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php
        // Display flash messages
        $flash_messages = getFlash();
        foreach ($flash_messages as $flash):
            $alert_type = $flash['type'] === 'error' ? 'danger' : $flash['type'];
        ?>
            <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>

        <?php echo $content; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
