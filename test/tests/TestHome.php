<?php
// test/tests/TestHome.php

require_once dirname(__DIR__) . '/TestHelper.php';

// Test home page
TestHelper::runTest('Home page loads correctly', function() {
    $response = TestHelper::makeRequest('index.php');
    
    if (!TestHelper::assertStatusCode($response, 200)) {
        return "Expected status 200, got: " . $response['status'];
    }
    
    if (!TestHelper::assertContains($response, 'Welcome to')) {
        return "Home page does not contain expected content";
    }
    
    return true;
});

TestHelper::runTest('Home page has login/register links', function() {
    $response = TestHelper::makeRequest('index.php');
    
    if (!TestHelper::assertContains($response, 'auth/login.php')) {
        return "Home page does not contain login link";
    }
    
    if (!TestHelper::assertContains($response, 'auth/register.php')) {
        return "Home page does not contain register link";
    }
    
    return true;
});