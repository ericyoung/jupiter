<?php
require_once "includes/db.php";

echo "Database connection test...<br>";
$stmt = $pdo->query("SELECT VERSION()");
$version = $stmt->fetch();
echo "Success! MySQL version: " . $version["VERSION()"] . "<br>";
?>
