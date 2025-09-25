<?php
// Test database connection
require_once 'includes/db.php';

echo "Testing database connection...<br>";

try {
    $stmt = $pdo->query("SELECT VERSION()");
    $version = $stmt->fetch();
    echo "Database connection successful!<br>";
    echo "MySQL version: " . $version['VERSION()'] . "<br>";

    // Test users table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch();
    echo "Users in database: " . $count['count'] . "<br>";

    $stmt = $pdo->query("SELECT id, name, email, role FROM users LIMIT 5");
    $users = $stmt->fetchAll();
    echo "First 5 users:<br>";
    foreach ($users as $user) {
        echo "- ID: {$user['id']}, Name: {$user['name']}, Email: {$user['email']}, Role: {$user['role']}<br>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>
