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
    setFlash('error', 'You do not have permission to edit tours.');
    header('Location: ../dashboard.php');
    exit;
}

// Get tour ID from query parameter
$tourId = $_GET['id'] ?? '';
if (empty($tourId)) {
    setFlash('error', 'No tour ID provided.');
    header('Location: list.php');
    exit;
}

// Get tour data
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM tours WHERE id = ?");
$stmt->execute([$tourId]);
$tour = $stmt->fetch();

if (!$tour) {
    setFlash('error', 'Tour not found.');
    header('Location: list.php');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        setFlash('error', 'Invalid request. Please try again.');
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . urlencode($tourId));
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
            $stmt = $pdo->prepare("UPDATE tours SET headliner = ?, support = ?, intro_line = ?, outro_line = ?, produced_by = ?, active = ? WHERE id = ?");
            $result = $stmt->execute([$headliner, $support, $introLine, $outroLine, $producedBy, $active, $tourId]);

            if ($result) {
                setFlash('success', 'Tour updated successfully!');
                header('Location: view.php?id=' . urlencode($tourId));
                exit;
            } else {
                $errors[] = 'Failed to update tour.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        setFlash('error', implode('<br>', $errors));
    }

    // Refresh tour data if there were validation errors
    $stmt = $pdo->prepare("SELECT * FROM tours WHERE id = ?");
    $stmt->execute([$tourId]);
    $tour = $stmt->fetch();
}

$csrf_token = generateCSRFToken();

// If form was submitted with errors, use POST data; otherwise use DB data
$headliner = htmlspecialchars($_POST['headliner'] ?? $tour['headliner'] ?? '');
$support = htmlspecialchars($_POST['support'] ?? $tour['support'] ?? '');
$introLine = htmlspecialchars($_POST['intro_line'] ?? $tour['intro_line'] ?? '');
$outroLine = htmlspecialchars($_POST['outro_line'] ?? $tour['outro_line'] ?? '');
$producedBy = htmlspecialchars($_POST['produced_by'] ?? $tour['produced_by'] ?? '');
$active = isset($_POST['active']) ? 1 : (int)($tour['active'] ?? 1);

$title = "Edit Tour - " . SITE_NAME;
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3>Edit Tour: <?php echo $headliner; ?></h3>
                <a href="view.php?id=<?php echo urlencode($tourId); ?>" class="btn btn-sm btn-info">View Tour</a>
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
                        <button type="submit" class="btn btn-primary">Update Tour</button>
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