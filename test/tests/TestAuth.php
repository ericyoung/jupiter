<?php
// test/tests/TestAuth.php

require_once dirname(__DIR__) . "/TestHelper.php";

// Test login page
TestHelper::runTest("Login page loads correctly", function() {
    $response = TestHelper::makeRequest("auth/login.php");
    
    if (!TestHelper::assertStatusCode($response, 200)) {
        return "Expected status 200, got: " . $response["status"];
    }
    
    if (!TestHelper::assertContains($response, "Login")) {
        return "Login page does not contain expected content";
    }
    
    if (!TestHelper::assertContains($response, "csrf_token")) {
        return "Login page does not contain CSRF token";
    }
    
    return true;
});

// Test register page
TestHelper::runTest("Register page loads correctly", function() {
    $response = TestHelper::makeRequest("auth/register.php");
    
    if (!TestHelper::assertStatusCode($response, 200)) {
        return "Expected status 200, got: " . $response["status"];
    }
    
    if (!TestHelper::assertContains($response, "Register")) {
        return "Register page does not contain expected content";
    }
    
    if (!TestHelper::assertContains($response, "csrf_token")) {
        return "Register page does not contain CSRF token";
    }
    
    return true;
});
