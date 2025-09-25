<?php
// test/tests/TestClient.php

require_once dirname(__DIR__) . '/TestHelper.php';

TestHelper::runTest('Client dashboard requires authentication', function() {
    $response = TestHelper::makeRequest('clients/dashboard.php');
    
    // Should redirect to login if not authenticated (302 or similar redirect)
    if (!TestHelper::assertStatusCode($response, '302') && 
        !TestHelper::assertContains($response, 'login.php')) {
        return "Client dashboard should redirect unauthenticated users to login";
    }
    
    return true;
});

TestHelper::runTest('Client profile requires authentication', function() {
    $response = TestHelper::makeRequest('clients/profile.php');
    
    // Should redirect to login if not authenticated
    if (!TestHelper::assertStatusCode($response, '302') && 
        !TestHelper::assertContains($response, 'login.php')) {
        return "Client profile should redirect unauthenticated users to login";
    }
    
    return true;
});