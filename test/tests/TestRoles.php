<?php
// test/tests/TestRoles.php

require_once dirname(__DIR__) . '/TestHelper.php';

TestHelper::runTest('Role management requires superadmin authentication', function() {
    $response = TestHelper::makeRequest('admin/roles/list.php');
    
    // Should redirect to login or show permission error if not superadmin
    if (!TestHelper::assertStatusCode($response, '302') && 
        !(TestHelper::assertContains($response, 'login.php') || 
          TestHelper::assertContains($response, 'permission'))) {
        return "Role management should restrict non-superadmin users";
    }
    
    return true;
});

TestHelper::runTest('Role creation requires superadmin authentication', function() {
    $response = TestHelper::makeRequest('admin/roles/create.php');
    
    // Should redirect to login or show permission error if not superadmin
    if (!TestHelper::assertStatusCode($response, '302') && 
        !(TestHelper::assertContains($response, 'login.php') || 
          TestHelper::assertContains($response, 'permission'))) {
        return "Role creation should restrict non-superadmin users";
    }
    
    return true;
});