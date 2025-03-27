<?php
/**
 * House Rental Management System
 * Tenant Dashboard
 */

// Include initialization file
require_once '../includes/init.php';

// Check if user is logged in as tenant
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'tenant') {
    redirect(BASE_URL . '/tenant/login.php');
}

// Page title
$page_title = 'Dashboard';

// Get tenant details
$tenant_id = $_SESSION['rental_user']['id'];
$db->query("SELECT * FROM tenant_accounts WHERE id = :id");
$db->bind(':id', $tenant_id);
$tenant_account = $db->single();

// Get booking requests
$house = new House();
$booking_requests = $house->getTenantBookingRequests($tenant_id);

// Check if tenant has active booking/tenancy
$db->query("SELECT br.*, h.house_no, h.description, h.price, 
           COALESCE(h.image_path, 'assets/img/house-default.jpg') as image_path, 
           c.name as category, t.date_in, t.id as tenant_record_id 
           FROM booking_requests br 
           INNER JOIN houses h ON br.house_id = h.id 
           LEFT JOIN categories c ON h.category_id = c.id 
           LEFT JOIN tenants t ON (t.booking_request_id = br.id)
           WHERE br.tenant_id = :tenant_id AND br.status = 1
           LIMIT 1");
$db->bind(':tenant_id', $tenant_id);
$active_booking = $db->single();

// Get payment history if tenant has active booking
if($active_booking && isset($active_booking['tenant_record_id'])) {
    $db->query("SELECT * FROM payments WHERE tenant_id = :tenant_id ORDER BY date_created DESC LIMIT 5");
    $db->bind(':tenant_id', $active_booking['tenant_record_id']);
    $payments = $db->resultSet();
    
    // Calculate next payment due date
    if(isset($active_booking['date_in'])) {
        $move_in_date = new DateTime($active_booking['date_in']);
        $today = new DateTime();
        
        // Calculate next payment date based on monthly cycle
        $next_payment_date = clone $move_in_date;
        while($next_payment_date < $today) {
            $next_payment_date->modify('+1 month');
        }
        
        $days_until_payment = $today->diff($next_payment_date)->days;
    }
}

// Count pending requests
$db->query("SELECT COUNT(*) as count FROM booking_requests WHERE tenant_id = :tenant_id AND status = 0");
$db->bind(':tenant_id', $tenant_id);
$pending_count = $db->single()['count'];

// Count total bookings
$db->query("SELECT COUNT(*) as count FROM booking_requests WHERE tenant_id = :tenant_id");
$db->bind(':tenant_id', $tenant_id);
$total_bookings = $db->single()['count'];
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<!-- Dashboard Section -->
<section class="py-5">
    <div class="container">
        <!-- Welcome Banner -->
        <div class="card bg-primary text-white mb-4 welcome-card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-1">Welcome, <?php echo $tenant_account['firstname']; ?>!</h2>
                        <p class="lead mb-0">Manage your rental properties and payments from your personal dashboard.</p>
                    </div>
                    <div class="col-md-4 text-md-right">
                        <a href="<?php echo BASE_URL; ?>/houses.php" class="btn btn-light">
                            <i class="fas fa-search mr-2"></i>Browse Properties
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Booking Requests</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_bookings; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-bookmark fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Pending Requests</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_count; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clock fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Active Rentals</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo isset($active_booking) ? 1 : 0; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-home fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="row">
            <!-- Active Rental/Booking -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <?php echo isset($active_booking) ? 'Your Current Rental' : 'No Active Rental'; ?>
                        </h6>
                        <?php if(!isset($active_booking)): ?>
                        <a href="<?php echo BASE_URL; ?>/houses.php" class="btn btn-sm btn-primary">Find a Property</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if(isset($active_booking)): ?>
                        <div class="row">
                            <div class="col-md-4 mb-4 mb-md-0">
                                <img src="<?php echo BASE_URL . '/' . $active_booking['image_path']; ?>" alt="<?php echo $active_booking['house_no']; ?>" class="img-fluid rounded">
                            </div>
                            <div class="col-md-8">
                                <h4><?php echo $active_booking['house_no']; ?></h4>
                                <p class="badge badge-primary"><?php echo $active_booking['category']; ?></p>
                                <p><?php echo $active_booking['description']; ?></p>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="font-weight-bold">Monthly Rent: ₹<?php echo number_format($active_booking['price'], 2); ?></span>
                                    <span class="badge badge-success">Active</span>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <span class="label">Move-in Date:</span>
                                            <span class="value"><?php echo date('M d, Y', strtotime($active_booking['date_in'])); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <span class="label">Next Payment:</span>
                                            <span class="value">
                                                <?php 
                                                if(isset($next_payment_date)) {
                                                    echo $next_payment_date->format('M d, Y');
                                                    echo ' <span class="badge badge-warning">' . $days_until_payment . ' days left</span>';
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <a href="<?php echo BASE_URL; ?>/tenant/make_payment.php" class="btn btn-success">
                                        <i class="fas fa-money-bill-wave mr-2"></i>Make Payment
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>/tenant/maintenance.php" class="btn btn-secondary">
                                        <i class="fas fa-tools mr-2"></i>Request Maintenance
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <img src="<?php echo IMG_PATH; ?>/no-property.svg" alt="No Active Rental" class="img-fluid mb-3" style="max-width: 200px;">
                            <h5>You don't have an active rental yet</h5>
                            <p>Browse available properties and submit a booking request to get started.</p>
                            <a href="<?php echo BASE_URL; ?>/houses.php" class="btn btn-primary">
                                <i class="fas fa-search mr-2"></i>Find a Property
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Booking Requests -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Booking Requests</h6>
                    </div>
                    <div class="card-body">
                        <?php if(empty($booking_requests)): ?>
                        <div class="text-center py-3">
                            <p>You haven't made any booking requests yet.</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Price</th>
                                        <th>Move-in Date</th>
                                        <th>Status</th>
                                        <th>Requested</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($booking_requests as $request): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/house_details.php?id=<?php echo $request['house_id']; ?>" class="font-weight-bold">
                                                <?php echo $request['house_no']; ?>
                                            </a>
                                        </td>
                                        <td>₹<?php echo number_format($request['price'], 2); ?>/month</td>
                                        <td><?php echo date('M d, Y', strtotime($request['move_in_date'])); ?></td>
                                        <td>
                                            <?php
                                            switch($request['status']) {
                                                case 0:
                                                    echo '<span class="badge badge-warning">Pending</span>';
                                                    break;
                                                case 1:
                                                    echo '<span class="badge badge-success">Approved</span>';
                                                    break;
                                                case 2:
                                                    echo '<span class="badge badge-danger">Rejected</span>';
                                                    break;
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($request['date_created'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="<?php echo BASE_URL; ?>/tenant/bookings.php" class="btn btn-outline-primary btn-sm">
                                View All Booking Requests
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Profile Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Account Information</h6>
                    </div>
                    <div class="card-body text-center">
                        <img src="<?php echo IMG_PATH; ?>/tenant-avatar.png" alt="Tenant Avatar" class="img-fluid rounded-circle mb-3" style="width: 120px;">
                        <h5><?php echo $tenant_account['firstname'] . ' ' . $tenant_account['lastname']; ?></h5>
                        <p class="text-muted"><?php echo $tenant_account['email']; ?></p>
                        <hr>
                        <div class="text-left">
                            <p><i class="fas fa-phone-alt mr-2"></i> <?php echo $tenant_account['contact']; ?></p>
                            <p><i class="fas fa-user mr-2"></i> <?php echo $tenant_account['username']; ?></p>
                            <p><i class="fas fa-calendar-alt mr-2"></i> Member since <?php echo date('M Y', strtotime($tenant_account['date_created'])); ?></p>
                        </div>
                        <a href="<?php echo BASE_URL; ?>/tenant/profile.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-user-edit mr-1"></i>Edit Profile
                        </a>
                    </div>
                </div>
                
                <!-- Recent Payments -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Payments</h6>
                    </div>
                    <div class="card-body">
                        <?php if(isset($active_booking) && !empty($payments)): ?>
                        <div class="payment-list">
                            <?php foreach($payments as $payment): ?>
                            <div class="payment-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0"><?php echo $payment['invoice']; ?></h6>
                                        <small class="text-muted"><?php echo date('M d, Y', strtotime($payment['date_created'])); ?></small>
                                    </div>
                                    <div>
                                        <span class="font-weight-bold">₹<?php echo number_format($payment['amount'], 2); ?></span>
                                        <?php
                                        $statusClass = 'secondary';
                                        $statusText = 'Pending';
                                        
                                        if($payment['status'] == 1) {
                                            $statusClass = 'success';
                                            $statusText = 'Approved';
                                        } elseif($payment['status'] == 2) {
                                            $statusClass = 'danger';
                                            $statusText = 'Rejected';
                                        }
                                        ?>
                                        <span class="badge badge-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="<?php echo BASE_URL; ?>/tenant/payments.php" class="btn btn-outline-primary btn-sm">
                                View All Payments
                            </a>
                        </div>
                        <?php elseif(isset($active_booking)): ?>
                        <div class="text-center py-3">
                            <p>No payment records found.</p>
                            <a href="<?php echo BASE_URL; ?>/tenant/make_payment.php" class="btn btn-success btn-sm">
                                <i class="fas fa-money-bill-wave mr-1"></i>Make Payment
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-3">
                            <p>No active rental to make payments for.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Quick Links</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <a href="<?php echo BASE_URL; ?>/houses.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-search mr-2"></i>Browse Available Houses
                            </a>
                            <a href="<?php echo BASE_URL; ?>/tenant/bookings.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-bookmark mr-2"></i>My Booking Requests
                            </a>
                            <a href="<?php echo BASE_URL; ?>/tenant/payments.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-money-bill-wave mr-2"></i>Payment History
                            </a>
                            <a href="<?php echo BASE_URL; ?>/tenant/maintenance.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-tools mr-2"></i>Maintenance Requests
                            </a>
                            <a href="<?php echo BASE_URL; ?>/about.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-info-circle mr-2"></i>About Us
                            </a>
                            <a href="<?php echo BASE_URL; ?>/contact.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-envelope mr-2"></i>Contact Support
                            </a>
                            <a href="<?php echo BASE_URL; ?>/tenant/logout.php" class="list-group-item list-group-item-action list-group-item-danger">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Custom CSS for this page -->
<style>
    .welcome-card {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        border: none;
        border-radius: 10px;
    }
    
    .card {
        border-radius: 10px;
        overflow: hidden;
    }
    
    .border-left-primary {
        border-left: 4px solid #4e73df !important;
    }
    
    .border-left-success {
        border-left: 4px solid #1cc88a !important;
    }
    
    .border-left-warning {
        border-left: 4px solid #f6c23e !important;
    }
    
    .info-item {
        margin-bottom: 10px;
    }
    
    .info-item .label {
        font-weight: 600;
        color: #5a5c69;
        display: block;
    }
    
    .payment-item {
        padding: 10px 0;
        border-bottom: 1px solid #e3e6f0;
    }
    
    .payment-item:last-child {
        border-bottom: none;
    }
</style>

<?php include '../includes/footer.php'; ?>