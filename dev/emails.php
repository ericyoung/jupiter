<?php
require_once '../includes/functions.php';
require_once 'email_tracker.php';

// This page should only be accessible in development environment
if (ENVIRONMENT !== 'development') {
    header('Location: ../index.php');
    exit;
}

$message = '';
$error = '';

// Handle clear emails action
if (isset($_GET['action']) && $_GET['action'] === 'clear' && isset($_GET['confirm']) && $_GET['confirm'] === '1') {
    $emailTracker->clearAllEmails();
    $message = 'All emails cleared successfully.';
}

$title = "Emails - Development - " . SITE_NAME;
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3>Email Tracking</h3>
                <a href="?action=clear&confirm=1" class="btn btn-danger btn-sm" 
                   onclick="return confirm('Are you sure you want to clear all emails?')">Clear All Emails</a>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <p>This page shows emails that would have been sent in production.</p>
                
                <div class="table-responsive">
                    <table class="table table-dark">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>To</th>
                                <th>Subject</th>
                                <th>Sent At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $emails = array_reverse($emailTracker->getEmails(20)); // Get last 20 emails, newest first
                            if (empty($emails)): 
                            ?>
                                <tr>
                                    <td colspan="5" class="text-center">No emails sent yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($emails as $email): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($email['id']); ?></td>
                                    <td><?php echo htmlspecialchars($email['to']); ?></td>
                                    <td><?php echo htmlspecialchars($email['subject']); ?></td>
                                    <td><?php echo htmlspecialchars($email['timestamp']); ?></td>
                                    <td>
                                        <a href="view_email.php?id=<?php echo urlencode($email['id']); ?>" class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>