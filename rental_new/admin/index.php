<?php
/**
 * House Rental Management System
 * Admin Dashboard
 */

// Include initialization file
require_once '../includes/init.php';

// Check if user is logged in as admin
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    redirect(BASE_URL . '/admin/login.php');
}

// Include header
include '../includes/header.php';

// Page title
$page_title = 'Admin Dashboard';

// Initialize database
$db = new Database();

// Get total houses
$db->query("SELECT COUNT(*) as total FROM houses");
$total_houses = $db->single()['total'];

// Get occupied houses
$db->query("SELECT COUNT(*) as total FROM houses WHERE status = 1");
$occupied_houses = $db->single()['total'];

// Get vacant houses
$vacant_houses = $total_houses - $occupied_houses;

// Get total tenants
$db->query("SELECT COUNT(*) as total FROM tenants WHERE status = 1");
$total_tenants = $db->single()['total'];

// Get total pending booking requests
$db->query("SELECT COUNT(*) as total FROM booking_requests WHERE status = 0");
$pending_bookings = $db->single()['total'];

// Get total pending maintenance requests
// Check if maintenance_requests table exists
$maintenance_table_exists = true;
try {
    $db->query("SHOW TABLES LIKE 'maintenance_requests'");
    $result = $db->resultSet();
    if (count($result) == 0) {
        $maintenance_table_exists = false;
    }
} catch (Exception $e) {
    $maintenance_table_exists = false;
}

if ($maintenance_table_exists) {
    $db->query("SELECT COUNT(*) as total FROM maintenance_requests WHERE status = 0");
    $pending_maintenance = $db->single()['total'];
} else {
    // Table doesn't exist, set default value
    $pending_maintenance = 0;
}

// Get total pending payments
// Check if payments table has status column
$payments_status_exists = true;
try {
    $db->query("SHOW COLUMNS FROM payments LIKE 'status'");
    $result = $db->resultSet();
    if (count($result) == 0) {
        $payments_status_exists = false;
    }
} catch (Exception $e) {
    $payments_status_exists = false;
}

if ($payments_status_exists) {
    $db->query("SELECT COUNT(*) as total FROM payments WHERE status = 0");
    $pending_payments = $db->single()['total'];
} else {
    // Column doesn't exist, set default value
    $pending_payments = 0;
}

// Get total revenue
if ($payments_status_exists) {
    $db->query("SELECT SUM(amount) as total FROM payments WHERE status = 1");
} else {
    $db->query("SELECT SUM(amount) as total FROM payments");
}
$total_revenue = $db->single()['total'] ?: 0;

// Get recent booking requests
$db->query("SELECT br.*, CONCAT(ta.firstname, ' ', ta.lastname) as tenant_name, h.house_no
           FROM booking_requests br
           INNER JOIN tenant_accounts ta ON br.tenant_id = ta.id
           INNER JOIN houses h ON br.house_id = h.id
           ORDER BY br.date_created DESC LIMIT 5");
$recent_bookings = $db->resultSet();

// Get recent payments
if ($payments_status_exists) {
    $db->query("SELECT p.*, CONCAT(t.firstname, ' ', t.lastname) as tenant_name, h.house_no
               FROM payments p
               INNER JOIN tenants t ON p.tenant_id = t.id
               INNER JOIN houses h ON t.house_id = h.id
               ORDER BY p.date_created DESC LIMIT 5");
} else {
    // Query without relying on status column
    $db->query("SELECT p.*, CONCAT(t.firstname, ' ', t.lastname) as tenant_name, h.house_no,
               '0' as status
               FROM payments p
               INNER JOIN tenants t ON p.tenant_id = t.id
               INNER JOIN houses h ON t.house_id = h.id
               ORDER BY p.date_created DESC LIMIT 5");
}
$recent_payments = $db->resultSet();

// Get recent maintenance requests
if ($maintenance_table_exists) {
    $db->query("SELECT mr.*, CONCAT(t.firstname, ' ', t.lastname) as tenant_name, h.house_no
               FROM maintenance_requests mr
               INNER JOIN tenants t ON mr.tenant_id = t.id
               INNER JOIN houses h ON mr.house_id = h.id
               ORDER BY mr.date_created DESC LIMIT 5");
    $recent_maintenance = $db->resultSet();
} else {
    $recent_maintenance = array();
}

?>

<div class="admin-layout">
    <!-- Sidebar -->
    <?php include '../includes/admin_sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">Dashboard</h1>
                <div class="action-buttons">
                    <a href="<?php echo BASE_URL; ?>/admin/reports.php" class="btn btn-primary">
                        <i class="fas fa-download fa-sm"></i> Generate Reports
                    </a>
                </div>
            </div>
            
            <!-- Stats Cards Row -->
            <div class="row">
                <!-- Total Houses Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card primary h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-title text-primary">TOTAL HOUSES</div>
                                    <div class="stat-value"><?php echo $total_houses; ?></div>
                                </div>
                                <div class="stat-icon text-primary">
                                    <i class="fas fa-home"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Vacant Houses Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card success h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-title text-success">VACANT HOUSES</div>
                                    <div class="stat-value"><?php echo $vacant_houses; ?></div>
                                </div>
                                <div class="stat-icon text-success">
                                    <i class="fas fa-door-open"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Occupied Houses Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card info h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-title text-info">OCCUPIED HOUSES</div>
                                    <div class="stat-value"><?php echo $occupied_houses; ?></div>
                                </div>
                                <div class="stat-icon text-info">
                                    <i class="fas fa-door-closed"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Total Tenants Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card warning h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-title text-warning">TOTAL TENANTS</div>
                                    <div class="stat-value"><?php echo $total_tenants; ?></div>
                                </div>
                                <div class="stat-icon text-warning">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pending Items Row -->
            <div class="row">
                <!-- Pending Booking Requests -->
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card stat-card danger h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-title text-danger">PENDING BOOKINGS</div>
                                    <div class="stat-value"><?php echo $pending_bookings; ?></div>
                                    <a href="<?php echo BASE_URL; ?>/admin/booking_requests.php" class="small text-danger">View Details <i class="fas fa-arrow-right"></i></a>
                                </div>
                                <div class="stat-icon text-danger">
                                    <i class="fas fa-clipboard-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Pending Maintenance Requests -->
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card stat-card primary h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-title text-primary">MAINTENANCE REQUESTS</div>
                                    <div class="stat-value"><?php echo $pending_maintenance; ?></div>
                                    <a href="<?php echo BASE_URL; ?>/admin/maintenance_requests.php" class="small text-primary">View Details <i class="fas fa-arrow-right"></i></a>
                                </div>
                                <div class="stat-icon text-primary">
                                    <i class="fas fa-tools"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Revenue -->
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card stat-card success h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-title text-success">TOTAL REVENUE</div>
                                    <div class="stat-value"><?php echo get_setting('currency_symbol') . number_format($total_revenue, 2); ?></div>
                                    <a href="<?php echo BASE_URL; ?>/admin/payments.php" class="small text-success">View Details <i class="fas fa-arrow-right"></i></a>
                                </div>
                                <div class="stat-icon text-success">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity Row -->
            <div class="row">
                <!-- Recent Booking Requests -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="font-weight-bold text-primary mb-0">Recent Booking Requests</h6>
                            <a href="<?php echo BASE_URL; ?>/admin/booking_requests.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Tenant</th>
                                            <th>House</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($recent_bookings)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No recent booking requests</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach($recent_bookings as $booking): ?>
                                                <tr>
                                                    <td><?php echo $booking['tenant_name']; ?></td>
                                                    <td><?php echo $booking['house_no']; ?></td>
                                                    <td>
                                                        <?php if($booking['status'] == 0): ?>
                                                            <span class="badge badge-warning">Pending</span>
                                                        <?php elseif($booking['status'] == 1): ?>
                                                            <span class="badge badge-success">Approved</span>
                                                        <?php elseif($booking['status'] == 2): ?>
                                                            <span class="badge badge-danger">Rejected</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($booking['date_created'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Payments -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="font-weight-bold text-primary mb-0">Recent Payments</h6>
                            <a href="<?php echo BASE_URL; ?>/admin/payments.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Tenant</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($recent_payments)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No recent payments</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach($recent_payments as $payment): ?>
                                                <tr>
                                                    <td><?php echo $payment['tenant_name']; ?></td>
                                                    <td><?php echo get_setting('currency_symbol') . number_format($payment['amount'], 2); ?></td>
                                                    <td>
                                                        <?php if($payment['status'] == 0): ?>
                                                            <span class="badge badge-warning">Pending</span>
                                                        <?php elseif($payment['status'] == 1): ?>
                                                            <span class="badge badge-success">Approved</span>
                                                        <?php elseif($payment['status'] == 2): ?>
                                                            <span class="badge badge-danger">Rejected</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($payment['date_created'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <footer class="footer">
            <div class="footer-text">
                <span>Copyright &copy; <?php echo get_setting('site_name'); ?> <?php echo date('Y'); ?></span>
            </div>
            <div class="footer-version">
                <span>Version 1.0</span>
            </div>
        </footer>
    </div>
</div>

<!-- JavaScript for Sidebar Toggle -->
<script>
    $(document).ready(function() {
        // Toggle sidebar on mobile
        $('#sidebarToggle').on('click', function() {
            $('.sidebar').toggleClass('show');
        });
        
        // Initialize DataTables
        if($.fn.DataTable) {
            $('.dataTable').DataTable({
                responsive: true,
                pageLength: 5,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search..."
                }
            });
        }
    });
</script>

<?php include '../includes/admin_footer.php'; ?>
