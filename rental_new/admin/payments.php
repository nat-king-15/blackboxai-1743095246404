<?php
/**
 * House Rental Management System
 * Admin Payments Management
 */

// Include initialization file
require_once '../includes/init.php';

// Check if user is logged in as admin
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    redirect(BASE_URL . '/admin/login.php');
}

// Page title
$page_title = 'Payments Management';

// Initialize database
$db = new Database();

// Initialize Payment class
$payment = new Payment();

// Handle form submissions
$success_msg = '';
$error_msg = '';

// Process payment confirmation/rejection
if(isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $payment_id = (int)$_GET['id'];
    $admin_notes = isset($_POST['admin_notes']) ? trim($_POST['admin_notes']) : '';
    
    if($action == 'confirm') {
        // Confirm payment
        $result = $payment->updatePaymentStatus($payment_id, 1, $admin_notes);
        
        if($result) {
            $success_msg = "Payment confirmed successfully.";
        } else {
            $error_msg = "Failed to confirm payment.";
        }
    } elseif($action == 'reject') {
        // Reject payment
        $result = $payment->updatePaymentStatus($payment_id, 2, $admin_notes);
        
        if($result) {
            $success_msg = "Payment rejected successfully.";
        } else {
            $error_msg = "Failed to reject payment.";
        }
    }
}

// Get all payments with tenant and house details
$db->query("SELECT p.*, 
           CONCAT(ta.firstname, ' ', ta.lastname) as tenant_name, ta.email as tenant_email, ta.phone as tenant_phone,
           h.house_no, h.description as house_description, h.price,
           CASE 
               WHEN p.status = 0 THEN 'Pending' 
               WHEN p.status = 1 THEN 'Confirmed' 
               WHEN p.status = 2 THEN 'Rejected' 
           END as status_text 
           FROM payments p 
           INNER JOIN tenant_accounts ta ON p.tenant_id = ta.id 
           INNER JOIN houses h ON p.house_id = h.id 
           ORDER BY 
               CASE WHEN p.status = 0 THEN 0 ELSE 1 END, 
               p.date_paid DESC");
$payments = $db->resultSet();

// Get payment details if ID is provided
$payment_details = null;
if(isset($_GET['view']) && !empty($_GET['view'])) {
    $view_id = (int)$_GET['view'];
    
    $db->query("SELECT p.*, 
               CONCAT(ta.firstname, ' ', ta.lastname) as tenant_name, 
               ta.email as tenant_email, ta.phone as tenant_phone,
               h.house_no, h.description as house_description, h.price, h.address,
               CASE 
                   WHEN p.status = 0 THEN 'Pending' 
                   WHEN p.status = 1 THEN 'Confirmed' 
                   WHEN p.status = 2 THEN 'Rejected' 
               END as status_text 
               FROM payments p 
               INNER JOIN tenant_accounts ta ON p.tenant_id = ta.id 
               INNER JOIN houses h ON p.house_id = h.id 
               WHERE p.id = :id");
    $db->bind(':id', $view_id);
    $payment_details = $db->single();
}

// Get payment statistics
$db->query("SELECT 
           SUM(CASE WHEN status = 1 THEN amount ELSE 0 END) as total_confirmed,
           SUM(CASE WHEN