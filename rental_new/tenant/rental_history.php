<?php
/**
 * House Rental Management System
 * Tenant Rental History
 */

// Include initialization file
require_once '../includes/init.php';

// Check if user is logged in as tenant
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'tenant') {
    redirect(BASE_URL . '/tenant/login.php');
}

// Page title
$page_title = 'Rental History';

// Get tenant details
$tenant_id = $_SESSION['rental_user']['id'];
$db->query("SELECT * FROM tenant_accounts WHERE id = :id");
$db->bind(':id', $tenant_id);
$tenant_account = $db->single();

// Get all booking requests (past and present)
$db->query("SELECT br.*, h.house_no, h.description, h.price, 
           COALESCE(h.image_path, 'assets/img/house-default.jpg') as image_path, 
           c.name as category, t.date_in, t.id as tenant_record_id,
           CASE 
               WHEN br.status = 0 THEN 'Pending'
               WHEN br.status = 1 THEN 'Approved'
               WHEN br.status = 2 THEN 'Rejected'
           END as status_text,
           CASE 
               WHEN br.status = 0 THEN 'warning'
               WHEN br.status = 1 THEN 'success'
               WHEN br.status = 2 THEN 'danger'
           END as status_class
           FROM booking_requests br 
           INNER JOIN houses h ON br.house_id = h.id 
           LEFT JOIN categories c ON h.category_id = c.id 
           LEFT JOIN tenants t ON (t.booking_request_id = br.id)
           WHERE br.tenant_id = :tenant_id
           ORDER BY br.date_created DESC");
$db->bind(':tenant_id', $tenant_id);
$rental_history = $db->resultSet();

// Include header and navbar
include '../includes/header.php';
include '../includes/navbar.php';
?>

<!-- Rental History Section -->
<section class="py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Rental History</h4>
                        <a href="<?php echo BASE_URL; ?>/tenant/dashboard.php" class="btn btn-light btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
                        </a>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">View your complete rental history, including past and current rentals.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if(empty($rental_history)): ?>
        <div class="alert alert-info" role="alert">
            <h4 class="alert-heading">No Rental History</h4>
            <p>You haven't made any booking requests yet. Browse available houses to make your first booking request.</p>
            <hr>
            <a href="<?php echo BASE_URL; ?>/houses.php" class="btn btn-primary">
                <i class="fas fa-search mr-2"></i>Browse Houses
            </a>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach($rental_history as $rental): ?>
            <div class="col-md-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">House #<?php echo $rental['house_no']; ?></h5>
                        <span class="badge badge-<?php echo $rental['status_class']; ?> px-3 py-2">
                            <?php echo $rental['status_text']; ?>
                        </span>
                    </div>
                    <div class="row no-gutters">
                        <div class="col-md-4">
                            <img src="<?php echo BASE_URL . '/' . $rental['image_path']; ?>" class="card-img h-100" alt="House Image" style="object-fit: cover;">
                        </div>
                        <div class="col-md-8">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted"><?php echo $rental['category']; ?></h6>
                                <p class="card-text">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-alt mr-1"></i>Requested on: <?php echo date('M d, Y', strtotime($rental['date_created'])); ?>
                                    </small>
                                </p>
                                <p class="card-text">
                                    <strong>Price:</strong> <?php echo get_setting('currency_symbol') . number_format($rental['price'], 2); ?> / month
                                </p>
                                <p class="card-text">
                                    <strong>Move-in Date:</strong> <?php echo date('M d, Y', strtotime($rental['move_in_date'])); ?>
                                </p>
                                <?php if($rental['tenant_record_id']): ?>
                                <p class="card-text">
                                    <strong>Actual Move-in:</strong> <?php echo date('M d, Y', strtotime($rental['date_in'])); ?>
                                </p>
                                <?php endif; ?>
                                <?php if($rental['status'] == 1): ?>
                                <div class="alert alert-success py-2">
                                    <small><i class="fas fa-check-circle mr-1"></i>This booking was approved</small>
                                </div>
                                <?php elseif($rental['status'] == 2): ?>
                                <div class="alert alert-danger py-2">
                                    <small><i class="fas fa-times-circle mr-1"></i>This booking was rejected</small>
                                </div>
                                <?php if(!empty($rental['notes'])): ?>
                                <p class="card-text">
                                    <strong>Reason:</strong> <?php echo $rental['notes']; ?>
                                </p>
                                <?php endif; ?>
                                <?php endif; ?>
                                
                                <div class="mt-3">
                                    <a href="<?php echo BASE_URL; ?>/houses.php?id=<?php echo $rental['house_id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-info-circle mr-1"></i>View House
                                    </a>
                                    <?php if($rental['tenant_record_id']): ?>
                                    <a href="<?php echo BASE_URL; ?>/tenant/payments.php?tenant_id=<?php echo $rental['tenant_record_id']; ?>" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-money-bill-wave mr-1"></i>View Payments
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if(!empty($rental['message'])): ?>
                    <div class="card-footer bg-light">
                        <small class="text-muted"><strong>Your Message:</strong> <?php echo $rental['message']; ?></small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
