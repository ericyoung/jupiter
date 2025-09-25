<?php
require_once '../includes/functions.php';

// Check if user is already logged in and redirect accordingly
checkAlreadyLoggedIn();

$email = $_GET['email'] ?? '';
$message = '';
$error = '';

if (!empty($email)) {
    $user = getUserByEmail($email);
    
    if ($user) {
        if ($user['is_active']) {
            setFlash('error', 'This account is already confirmed.');
            header('Location: login.php');
            exit;
        } else {
            // Generate a new token and send confirmation email
            $token = generateToken();
            
            global $pdo;
            $stmt = $pdo->prepare("UPDATE users SET activation_token = ? WHERE email = ?");
            $result = $stmt->execute([$token, $email]);
            
            if ($result) {
                sendConfirmationEmail($email, $user['name'], $token);
                setFlash('success', 'Confirmation email has been resent. Please check your email.');
            } else {
                setFlash('error', 'Failed to resend confirmation email. Please try again.');
            }
        }
    } else {
        setFlash('error', 'User not found.');
    }
} else {
    setFlash('error', 'No email provided.');
}

// Redirect back to login
header('Location: login.php');
exit;
?>