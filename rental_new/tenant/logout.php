<?php
/**
 * House Rental Management System
 * Tenant Logout
 */

// Include initialization file
require_once '../includes/init.php';

// Create User object
$user = new User();

// Log the user out
$user->logout();

// Redirect to login page with success message
$_SESSION['flash_message'] = 'You have been successfully logged out.';
$_SESSION['flash_type'] = 'success';

// Redirect to login page
redirect(BASE_URL . '/tenant/login.php');
?>
