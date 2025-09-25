<?php
// test/tests/TestUsers.php

require_once dirname(__DIR__) . '/TestHelper.php';

TestHelper::runTest('User management requires admin authentication', function() {
    $response = TestHelper::makeRequest('admin/users/list.php');

    // Should redirect to login if not authenticated
    if (!TestHelper::assertStatusCode($response, '302') &&
        !TestHelper::assertContains($response, 'login.php')) {
        return "User management should redirect unauthenticated users to login";
    }

    return true;
});

TestHelper::runTest('User edit requires admin authentication', function() {
    $response = TestHelper::makeRequest('admin/users/edit.php?id=1');

    // Should redirect to login if not authenticated
    if (!TestHelper::assertStatusCode($response, '302') &&
        !TestHelper::assertContains($response, 'login.php')) {
        return "User edit should redirect unauthenticated users to login";
    }

    return true;
});
