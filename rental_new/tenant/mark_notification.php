<?php
/**
 * House Rental Management System
 * Mark Notification as Read
 */

// Include initialization file
require_once '../includes/init.php';

// Check if user is logged in as tenant
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'tenant') {
    redirect(BASE_URL . '/tenant/login.php');
}

// Get notification ID
$notification_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$tenant_id = $_SESSION['rental_user']['id'];

if($notification_id > 0) {
    // Mark notification as read
    $db->query("UPDATE notifications SET is_read = 1 
               WHERE id = :id AND user_id = :user_id AND user_type = 'tenant'");
    $db->bind(':id', $notification_id);
    $db->bind(':user_id', $tenant_id);
    $db->execute();
}

// Redirect back to notifications page
redirect(BASE_URL . '/tenant/notifications.php');
?>
