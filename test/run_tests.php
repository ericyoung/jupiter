#!/usr/bin/env php
<?php
// test/run_tests.php
// Simple test runner

require_once 'TestHelper.php';

echo "Jupiter PHP Project - Test Runner\n";
echo "================================\n\n";

// Example test files to run
$testFiles = [
    'TestAuth.php',
    'TestHome.php',
    'TestClient.php', 
    'TestAdmin.php',
    'TestUsers.php',
    'TestRoles.php'
];

foreach ($testFiles as $file) {
    $path = __DIR__ . '/tests/' . $file;
    
    if (file_exists($path)) {
        echo "Loading tests from $file...\n";
        require_once $path;
    } else {
        echo "Test file $file does not exist yet.\n";
    }
}

// Print summary
TestHelper::printSummary();

echo "\nTest execution completed.\n";