<?php
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit;
}

// Check if user has admin access (superadmin, executive, or accounts)
$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['superadmin', 'executive', 'accounts'];
if (!in_array($userRole, $allowedRoles)) {
    setFlash('error', 'You do not have permission to create tours.');
    header('Location: ../dashboard.php');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        setFlash('error', 'Invalid request. Please try again.');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $headliner = sanitizeInput($_POST['headliner'] ?? '');
    $support = sanitizeInput($_POST['support'] ?? '');
    $introLine = sanitizeInput($_POST['intro_line'] ?? '');
    $outroLine = sanitizeInput($_POST['outro_line'] ?? '');
    $producedBy = sanitizeInput($_POST['produced_by'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;

    // Validation
    $errors = [];
    if (empty($headliner)) {
        $errors[] = 'Headliner is required.';
    }

    if (empty($errors)) {
        try {
            global $pdo;
            $stmt = $pdo->prepare("INSERT INTO tours (headliner, support, intro_line, outro_line, produced_by, active) VALUES (?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([$headliner, $support, $introLine, $outroLine, $producedBy, $active]);

            if ($result) {
                $tourId = $pdo->lastInsertId();
                setFlash('success', 'Tour created successfully!');
                header('Location: view.php?id=' . $tourId);
                exit;
            } else {
                $errors[] = 'Failed to create tour.';
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

// If form was submitted with errors, use POST data
$headliner = htmlspecialchars($_POST['headliner'] ?? '');
$support = htmlspecialchars($_POST['support'] ?? '');
$introLine = htmlspecialchars($_POST['intro_line'] ?? '');
$outroLine = htmlspecialchars($_POST['outro_line'] ?? '');
$producedBy = htmlspecialchars($_POST['produced_by'] ?? '');
$active = isset($_POST['active']) ? 1 : 0;

$title = "Create Tour - " . SITE_NAME;
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3>Create New Tour</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="mb-3">
                        <label for="headliner" class="form-label">Headliner *</label>
                        <input type="text" class="form-control" id="headliner" name="headliner" value="<?php echo $headliner; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="support" class="form-label">Support</label>
                        <textarea class="form-control" id="support" name="support" rows="2"><?php echo $support; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="intro_line" class="form-label">Intro Line</label>
                        <textarea class="form-control" id="intro_line" name="intro_line" rows="2"><?php echo $introLine; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="outro_line" class="form-label">Outro Line</label>
                        <textarea class="form-control" id="outro_line" name="outro_line" rows="2"><?php echo $outroLine; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="produced_by" class="form-label">Produced By</label>
                        <textarea class="form-control" id="produced_by" name="produced_by" rows="2"><?php echo $producedBy; ?></textarea>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="active" name="active" value="1" <?php echo $active ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="active">Active</label>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="list.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create Tour</button>
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