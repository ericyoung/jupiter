<?php
require_once '../includes/functions.php';
require_once 'email_tracker.php';

// This page should only be accessible in development environment
if (ENVIRONMENT !== 'development') {
    header('Location: ../index.php');
    exit;
}

$email_id = $_GET['id'] ?? '';
$email = null;

if ($email_id) {
    $email = $emailTracker->getEmailById($email_id);
}

if (!$email) {
    setFlash('error', 'Email not found.');
    header('Location: emails.php');
    exit;
}

$title = "View Email - " . $email['subject'] . " - Development";
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3>View Email</h3>
                <a href="emails.php" class="btn btn-secondary">Back to Emails</a>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>To:</strong> <?php echo htmlspecialchars($email['to']); ?>
                </div>
                <div class="mb-3">
                    <strong>Subject:</strong> <?php echo htmlspecialchars($email['subject']); ?>
                </div>
                <div class="mb-3">
                    <strong>Sent At:</strong> <?php echo htmlspecialchars($email['timestamp']); ?>
                </div>
                <div class="mb-4">
                    <strong>Email Content:</strong>
                    <div class="border p-3 bg-dark" style="min-height: 200px;">
                        <?php echo $email['body']; // Allow HTML in email body ?>
                    </div>
                </div>
                
                <div>
                    <a href="emails.php" class="btn btn-secondary">Back to Emails</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>