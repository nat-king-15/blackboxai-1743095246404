<?php
/**
 * House Rental Management System
 * Admin Booking Requests Management
 */

// Include initialization file
require_once '../includes/init.php';

// Check if user is logged in as admin
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    redirect(BASE_URL . '/admin/login.php');
}

// Page title
$page_title = 'Booking Requests';

// Initialize database
$db = new Database();

// Handle form submissions
$success_msg = '';
$error_msg = '';

// Process booking request approval/rejection
if(isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $booking_id = (int)$_GET['id'];
    $admin_notes = isset($_POST['admin_notes']) ? trim($_POST['admin_notes']) : '';
    
    if($action == 'approve') {
        // Check if house is still available
        $db->query("SELECT br.*, h.house_no, ta.firstname, ta.lastname, ta.email 
                  FROM booking_requests br 
                  INNER JOIN houses h ON br.house_id = h.id 
                  INNER JOIN tenant_accounts ta ON br.tenant_id = ta.id
                  WHERE br.id = :id");
        $db->bind(':id', $booking_id);
        $booking = $db->single();
        
        if(!$booking) {
            $error_msg = "Booking request not found.";
        } else {
            // Check if house is already booked
            $db->query("SELECT COUNT(*) as count FROM booking_requests 
                       WHERE house_id = :house_id AND status = 1 AND id != :booking_id");
            $db->bind(':house_id', $booking['house_id']);
            $db->bind(':booking_id', $booking_id);
            $house_booked = $db->single();
            
            if($house_booked['count'] > 0) {
                $error_msg = "House #{$booking['house_no']} is already booked by another tenant.";
            } else {
                // Approve booking request
                $db->query("UPDATE booking_requests SET status = 1, admin_notes = :notes, date_updated = NOW() WHERE id = :id");
                $db->bind(':id', $booking_id);
                $db->bind(':notes', $admin_notes);
                
                if($db->execute()) {
                    // Create tenant record
                    $db->query("INSERT INTO tenants (tenant_id, house_id, booking_request_id, date_in) 
                               VALUES (:tenant_id, :house_id, :booking_id, NOW())");
                    $db->bind(':tenant_id', $booking['tenant_id']);
                    $db->bind(':house_id', $booking['house_id']);
                    $db->bind(':booking_id', $booking_id);
                    $db->execute();
                    
                    // Create notification for tenant
                    try {
                        $notification_title = "Booking Request Approved";
                        $notification_message = "Your booking request for House #{$booking['house_no']} has been approved.";
                        if (!empty($admin_notes)) {
                            $notification_message .= " Admin note: " . $admin_notes;
                        }
                        
                        $db->query("INSERT INTO notifications (user_id, title, message, type, is_read, date_created) 
                                  VALUES (:user_id, :title, :message, 1, 0, NOW())");
                        $db->bind(':user_id', $booking['tenant_id']);
                        $db->bind(':title', $notification_title);
                        $db->bind(':message', $notification_message);
                        $db->execute();
                        
                        // Send email notification if email function exists
                        if (function_exists('send_email')) {
                            $recipient_name = $booking['firstname'] . ' ' . $booking['lastname'];
                            $recipient_email = $booking['email'];
                            $email_subject = "Booking Request Approved - House #{$booking['house_no']}";
                            $email_body = "Dear $recipient_name,\n\n";
                            $email_body .= "Your booking request for House #{$booking['house_no']} has been approved.\n\n";
                            if (!empty($admin_notes)) {
                                $email_body .= "Admin Notes: $admin_notes\n\n";
                            }
                            $email_body .= "You can now proceed with the payment process. Please log in to your account for more details.\n\n";
                            $email_body .= "Thank you for choosing our service.\n\n";
                            $email_body .= "Regards,\n" . APP_NAME . " Team";
                            
                            send_email($recipient_email, $email_subject, $email_body);
                        }
                    } catch (Exception $e) {
                        // Continue even if notification creation fails
                        error_log("Failed to create approval notification: " . $e->getMessage());
                    }
                    
                    $success_msg = "Booking request approved successfully.";
                } else {
                    $error_msg = "Failed to approve booking request.";
                }
            }
        }
    } elseif($action == 'reject') {
        // Get booking details for notification
        $db->query("SELECT br.*, h.house_no, ta.firstname, ta.lastname, ta.email 
                  FROM booking_requests br 
                  INNER JOIN houses h ON br.house_id = h.id 
                  INNER JOIN tenant_accounts ta ON br.tenant_id = ta.id
                  WHERE br.id = :id");
        $db->bind(':id', $booking_id);
        $booking = $db->single();
        
        if(!$booking) {
            $error_msg = "Booking request not found.";
        } else {
            // Reject booking request
            $db->query("UPDATE booking_requests SET status = 2, admin_notes = :notes, date_updated = NOW() WHERE id = :id");
            $db->bind(':id', $booking_id);
            $db->bind(':notes', $admin_notes);
            
            if($db->execute()) {
                // Create notification for tenant
                try {
                    $notification_title = "Booking Request Rejected";
                    $notification_message = "Your booking request for House #{$booking['house_no']} has been rejected.";
                    if (!empty($admin_notes)) {
                        $notification_message .= " Reason: " . $admin_notes;
                    }
                    
                    $db->query("INSERT INTO notifications (user_id, title, message, type, is_read, date_created) 
                              VALUES (:user_id, :title, :message, 1, 0, NOW())");
                    $db->bind(':user_id', $booking['tenant_id']);
                    $db->bind(':title', $notification_title);
                    $db->bind(':message', $notification_message);
                    $db->execute();
                    
                    // Send email notification if email function exists
                    if (function_exists('send_email')) {
                        $recipient_name = $booking['firstname'] . ' ' . $booking['lastname'];
                        $recipient_email = $booking['email'];
                        $email_subject = "Booking Request Rejected - House #{$booking['house_no']}";
                        $email_body = "Dear $recipient_name,\n\n";
                        $email_body .= "We regret to inform you that your booking request for House #{$booking['house_no']} has been rejected.\n\n";
                        if (!empty($admin_notes)) {
                            $email_body .= "Reason: $admin_notes\n\n";
                        }
                        $email_body .= "You may submit a new booking request for another available house.\n\n";
                        $email_body .= "Thank you for your understanding.\n\n";
                        $email_body .= "Regards,\n" . APP_NAME . " Team";
                        
                        send_email($recipient_email, $email_subject, $email_body);
                    }
                } catch (Exception $e) {
                    // Continue even if notification creation fails
                    error_log("Failed to create rejection notification: " . $e->getMessage());
                }
                
                $success_msg = "Booking request rejected successfully.";
            } else {
                $error_msg = "Failed to reject booking request.";
            }
        }
    }
}

// Get all booking requests with tenant and house details
$db->query("SELECT br.*, 
           CONCAT(ta.firstname, ' ', ta.lastname) as tenant_name, ta.email as tenant_email, ta.contact as tenant_phone,
           h.house_no, h.description as house_description, h.price, c.name as category_name,
           CASE 
               WHEN br.status = 0 THEN 'Pending' 
               WHEN br.status = 1 THEN 'Approved' 
               WHEN br.status = 2 THEN 'Rejected' 
           END as status_text 
           FROM booking_requests br 
           INNER JOIN tenant_accounts ta ON br.tenant_id = ta.id 
           INNER JOIN houses h ON br.house_id = h.id 
           LEFT JOIN categories c ON h.category_id = c.id 
           ORDER BY 
               CASE WHEN br.status = 0 THEN 0 ELSE 1 END, 
               br.date_created DESC");
$booking_requests = $db->resultSet();

// Get booking request details if ID is provided
$booking_details = null;
if(isset($_GET['view']) && !empty($_GET['view'])) {
    $view_id = (int)$_GET['view'];
    
    $db->query("SELECT br.*, 
               CONCAT(ta.firstname, ' ', ta.lastname) as tenant_name, 
               ta.email as tenant_email, ta.contact as tenant_phone, ta.id_number, ta.emergency_contact,
               h.house_no, h.description as house_description, h.price, h.address, 
               c.name as category_name,
               CASE 
                   WHEN br.status = 0 THEN 'Pending' 
                   WHEN br.status = 1 THEN 'Approved' 
                   WHEN br.status = 2 THEN 'Rejected' 
               END as status_text 
               FROM booking_requests br 
               INNER JOIN tenant_accounts ta ON br.tenant_id = ta.id 
               INNER JOIN houses h ON br.house_id = h.id 
               LEFT JOIN categories c ON h.category_id = c.id 
               WHERE br.id = :id");
    $db->bind(':id', $view_id);
    $booking_details = $db->single();
}

?>

<?php include '../includes/header.php'; ?>

<div class="admin-layout">
    <!-- Sidebar -->
    <?php include '../includes/admin_sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="content">
        <div class="container-fluid p-4">
            <?php if(isset($_GET['view']) && $booking_details): ?>
                <!-- Booking Request Details View -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h2 class="page-title">Booking Request Details</h2>
                    </div>
                    <div class="col-md-6 text-right">
                        <a href="<?php echo BASE_URL; ?>/admin/booking_requests.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Booking Requests
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
                
                <!-- Rest of booking details content would be here -->
                
            <?php endif; ?>
            
            <!-- If this is the list view (not viewing a specific booking) -->
            <?php if(!isset($_GET['view']) || !$booking_details): ?>
                <!-- Booking Requests List View Content -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h2 class="page-title">Booking Requests</h2>
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
                
                <!-- Booking Requests Table -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">All Booking Requests</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="bookingRequestsTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Tenant</th>
                                        <th>House</th>
                                        <th>Move-in Date</th>
                                        <th>Status</th>
                                        <th>Date Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($booking_requests as $request): ?>
                                    <tr>
                                        <td><?php echo $request['tenant_name']; ?></td>
                                        <td><?php echo $request['house_no']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($request['move_in_date'])); ?></td>
                                        <td>
                                            <?php if($request['status'] == 0): ?>
                                                <span class="badge badge-warning">Pending</span>
                                            <?php elseif($request['status'] == 1): ?>
                                                <span class="badge badge-success">Approved</span>
                                            <?php elseif($request['status'] == 2): ?>
                                                <span class="badge badge-danger">Rejected</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($request['date_created'])); ?></td>
                                        <td>
                                            <a href="?view=<?php echo $request['id']; ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>