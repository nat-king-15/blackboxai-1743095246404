<?php
/**
 * House Rental Management System
 * Admin Maintenance Requests Management
 */

// Include initialization file
require_once '../includes/init.php';

// Check if user is logged in as admin
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    redirect(BASE_URL . '/admin/login.php');
}

// Page title
$page_title = 'Maintenance Requests';

// Initialize database
$db = new Database();

// Handle form submissions
$success_msg = '';
$error_msg = '';

// Process maintenance request status update
if(isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $request_id = (int)$_GET['id'];
    $admin_notes = isset($_POST['admin_notes']) ? trim($_POST['admin_notes']) : '';
    
    if($action == 'update_status') {
        $new_status = isset($_POST['status']) ? $_POST['status'] : '';
        $valid_statuses = ['pending', 'in_progress', 'completed', 'cancelled'];
        
        if(!in_array($new_status, $valid_statuses)) {
            $error_msg = "Invalid status selected.";
        } else {
            // Update maintenance request status
            $db->query("UPDATE maintenance_requests SET status = :status, admin_notes = :notes, date_updated = NOW() WHERE id = :id");
            $db->bind(':id', $request_id);
            $db->bind(':status', $new_status);
            $db->bind(':notes', $admin_notes);
            
            if($db->execute()) {
                $success_msg = "Maintenance request status updated successfully.";
            } else {
                $error_msg = "Failed to update maintenance request status.";
            }
        }
    }
}

// Get all maintenance requests with tenant and house details
$db->query("SELECT mr.*, 
           CONCAT(ta.firstname, ' ', ta.lastname) as tenant_name, ta.email as tenant_email, ta.phone as tenant_phone,
           h.house_no, h.description as house_description, h.address
           FROM maintenance_requests mr 
           INNER JOIN tenant_accounts ta ON mr.tenant_id = ta.id 
           INNER JOIN houses h ON mr.house_id = h.id 
           ORDER BY 
               CASE 
                   WHEN mr.priority = 'emergency' THEN 1
                   WHEN mr.priority = 'high' THEN 2
                   WHEN mr.priority = 'medium' THEN 3
                   WHEN mr.priority = 'low' THEN 4
               END,
               CASE WHEN mr.status = 'pending' THEN 0 ELSE 1 END, 
               mr.date_created DESC");
$maintenance_requests = $db->resultSet();

// Get maintenance request details if ID is provided
$request_details = null;
if(isset($_GET['view']) && !empty($_GET['view'])) {
    $view_id = (int)$_GET['view'];
    
    $db->query("SELECT mr.*, 
               CONCAT(ta.firstname, ' ', ta.lastname) as tenant_name, 
               ta.email as tenant_email, ta.phone as tenant_phone,
               h.house_no, h.description as house_description, h.address
               FROM maintenance_requests mr 
               INNER JOIN tenant_accounts ta ON mr.tenant_id = ta.id 
               INNER JOIN houses h ON mr.house_id = h.id 
               WHERE mr.id = :id");
    $db->bind(':id', $view_id);
    $request_details = $db->single();
}

// Maintenance request types for display
$request_types = array(
    'plumbing' => 'Plumbing Issue',
    'electrical' => 'Electrical Problem',
    'appliance' => 'Appliance Repair',
    'hvac' => 'Heating/Cooling',
    'structural' => 'Structural Damage',
    'pest' => 'Pest Control',
    'lock' => 'Lock/Key Issue',
    'other' => 'Other'
);

// Priority levels for display
$priority_levels = array(
    'low' => 'Low - Not Urgent',
    'medium' => 'Medium - Needs Attention',
    'high' => 'High - Urgent Issue',
    'emergency' => 'Emergency - Immediate Action Required'
);

// Status options for display
$status_options = array(
    'pending' => 'Pending Review',
    'in_progress' => 'In Progress',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
);

?>

<?php include '../includes/header.php'; ?>

<div class="admin-layout">
    <!-- Sidebar -->
    <?php include '../includes/admin_sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="content">
        <div class="container-fluid p-4">
            <?php if(isset($_GET['view']) && $request_details): ?>
                <!-- Maintenance Request Details View -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h2 class="page-title">Maintenance Request Details</h2>
                    </div>
                    <div class="col-md-6 text-right">
                        <a href="<?php echo BASE_URL; ?>/admin/maintenance_requests.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Maintenance Requests
                        </a>
                    </div>
                </div>
                
                <?php if(isset($success_msg) && !empty($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_msg; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>
                
                <?php if(isset($error_msg) && !empty($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_msg; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Request Details Card -->
                    <div class="col-md-8 mb-4">
                        <div class="card shadow">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Request #<?php echo $request_details['request_id']; ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <h6 class="font-weight-bold">Request Information</h6>