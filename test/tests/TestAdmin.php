<?php
// test/tests/TestAdmin.php

require_once dirname(__DIR__) . '/TestHelper.php';

TestHelper::runTest('Admin dashboard requires authentication', function() {
    $response = TestHelper::makeRequest('admin/dashboard.php');
    
    // Should redirect to login if not authenticated
    if (!TestHelper::assertStatusCode($response, '302') && 
        !TestHelper::assertContains($response, 'login.php')) {
        return "Admin dashboard should redirect unauthenticated users to login";
    }
    
    return true;
});

TestHelper::runTest('Admin profile requires authentication', function() {
    $response = TestHelper::makeRequest('admin/profile.php');
    
    // Should redirect to login if not authenticated
    if (!TestHelper::assertStatusCode($response, '302') && 
        !TestHelper::assertContains($response, 'login.php')) {
        return "Admin profile should redirect unauthenticated users to login";
    }
    
    return true;
});