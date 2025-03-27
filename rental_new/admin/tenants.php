<?php
/**
 * House Rental Management System
 * Admin Tenants Management
 */

// Include initialization file
require_once '../includes/init.php';

// Check if user is logged in as admin
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    redirect(BASE_URL . '/admin/login.php');
}

// Page title
$page_title = 'Tenants Management';

// Initialize database
$db = new Database();

// Handle form submissions
$success_msg = '';
$error_msg = '';

// Get all tenant accounts with their active booking status
$db->query("SELECT ta.*, 
           (SELECT COUNT(*) FROM booking_requests br WHERE br.tenant_id = ta.id AND br.status = 1) as has_active_booking 
           FROM tenant_accounts ta 
           ORDER BY ta.lastname ASC, ta.firstname ASC");
$tenants = $db->resultSet();

// Get tenant details if ID is provided
$tenant_details = null;
if(isset($_GET['view']) && !empty($_GET['view'])) {
    $tenant_id = (int)$_GET['view'];
    
    // Get tenant account details
    $db->query("SELECT * FROM tenant_accounts WHERE id = :id");
    $db->bind(':id', $tenant_id);
    $tenant_details = $db->single();
    
    if($tenant_details) {
        // Get tenant's active booking/tenancy
        $db->query("SELECT br.*, h.house_no, h.description, h.price, 
                   COALESCE(h.image_path, 'assets/img/house-default.jpg') as image_path, 
                   c.name as category, t.date_in, t.id as tenant_record_id 
                   FROM booking_requests br 
                   INNER JOIN houses h ON br.house_id = h.id 
                   LEFT JOIN categories c ON h.category_id = c.id 
                   LEFT JOIN tenants t ON (t.booking_request_id = br.id) 
                   WHERE br.tenant_id = :tenant_id AND br.status = 1");
        $db->bind(':tenant_id', $tenant_id);
        $active_booking = $db->single();
        
        // Get tenant's booking history
        $db->query("SELECT br.*, h.house_no, h.description, h.price, 
                   c.name as category, 
                   CASE 
                       WHEN br.status = 0 THEN 'Pending' 
                       WHEN br.status = 1 THEN 'Approved' 
                       WHEN br.status = 2 THEN 'Rejected' 
                   END as status_text 
                   FROM booking_requests br 
                   INNER JOIN houses h ON br.house_id = h.id 
                   LEFT JOIN categories c ON h.category_id = c.id 
                   WHERE br.tenant_id = :tenant_id 
                   ORDER BY br.date_created DESC");
        $db->bind(':tenant_id', $tenant_id);
        $booking_history = $db->resultSet();
        
        // Get tenant's payment history
        $db->query("SELECT p.*, h.house_no, 
                   CASE 
                       WHEN p.status = 0 THEN 'Pending' 
                       WHEN p.status = 1 THEN 'Confirmed' 
                       WHEN p.status = 2 THEN 'Rejected' 
                   END as status_text 
                   FROM payments p 
                   INNER JOIN houses h ON p.house_id = h.id 
                   WHERE p.tenant_id = :tenant_id 
                   ORDER BY p.date_paid DESC");
        $db->bind(':tenant_id', $tenant_id);
        $payment_history = $db->resultSet();
        
        // Get tenant's maintenance request history
        $db->query("SELECT mr.*, h.house_no 
                   FROM maintenance_requests mr 
                   INNER JOIN houses h ON mr.house_id = h.id 
                   WHERE mr.tenant_id = :tenant_id 
                   ORDER BY mr.date_created DESC");
        $db->bind(':tenant_id', $tenant_id);
        $maintenance_history = $db->resultSet();
    }
}

?>

<?php include '../includes/header.php'; ?>

<div class="admin-layout">
    <!-- Sidebar -->
    <?php include '../includes/admin_sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="content">
        <div class="container-fluid p-4">
            <?php if(isset($_GET['view']) && $tenant_details): ?>
                <!-- Tenant Details View -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h2 class="page-title">Tenant Details</h2>
                    </div>
                    <div class="col-md-6 text-right">
                        <a href="<?php echo BASE_URL; ?>/admin/tenants.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Tenants List
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
                    <!-- Tenant Profile Card -->
                    <div class="col-md-4 mb-4">
                        <div class="card shadow">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Tenant Profile</h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-4">
                                    <img src="<?php echo BASE_URL; ?>/assets/img/tenant-avatar.png" alt="Tenant" class="img-fluid rounded-circle mb-3" width="120">
                                    <h4><?php echo $tenant_details['firstname'] . ' ' . $tenant_details['lastname']; ?></h4>
                                    <p class="text-muted">
                                        <i class="fas fa-user mr-2"></i><?php echo $tenant_details['username']; ?>
                                    </p>
                                </div>
                                
                                <div class="tenant-info">
                                    <p><strong>Email:</strong> <?php echo $tenant_details['email']; ?></p>
                                    <p><strong>Phone:</strong> <?php echo $tenant_details['phone']; ?></p>
                                    <p><strong>ID Number:</strong> <?php echo $tenant_details['id_number']; ?></p>
                                    <p><strong>Emergency Contact:</strong> <?php echo $tenant_details['emergency_contact']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Tenants List View -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h2 class="page-title">Tenants Management</h2>
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
                
                <!-- Tenants List Card -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">All Tenant Accounts</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="tenantsTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Contact</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($tenants as $tenant): ?>
                                    <tr>
                                        <td><?php echo $tenant['firstname'] . ' ' . $tenant['lastname']; ?></td>
                                        <td><?php echo $tenant['email']; ?></td>
                                        <td><?php echo isset($tenant['phone']) ? $tenant['phone'] : 'N/A'; ?></td>
                                        <td>
                                            <?php if($tenant['has_active_booking'] > 0): ?>
                                                <span class="badge badge-success">Active Tenant</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Registered Only</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?view=<?php echo $tenant['id']; ?>" class="btn btn-info btn-sm">
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