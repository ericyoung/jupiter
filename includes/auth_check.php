<?php
// This file provides functions to check user access to different sections
require_once 'functions.php';

/**
 * Check if user has access to client area
 */
function hasClientAccess() {
    return isLoggedIn() && isClient();
}

/**
 * Check if user has access to admin area
 */
function hasAdminAccess() {
    return isLoggedIn() && !isClient();
}

/**
 * Redirect to appropriate dashboard or login if not logged in
 */
function redirectToDashboard() {
    if (!isLoggedIn()) {
        header('Location: auth/login.php');
        exit;
    }
    
    // User is logged in, redirect based on role
    if (isClient()) {
        header('Location: clients/dashboard.php');
    } else {
        header('Location: admin/dashboard.php');
    }
    exit;
}