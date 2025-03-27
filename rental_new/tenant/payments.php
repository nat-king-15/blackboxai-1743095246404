<?php
/**
 * House Rental Management System
 * Tenant Payment History
 */

// Include initialization file
require_once '../includes/init.php';

// Check if user is logged in as tenant
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'tenant') {
    redirect(BASE_URL . '/tenant/login.php');
}

// Page title
$page_title = 'Payment History';

// Get tenant details
$tenant_id = $_SESSION['rental_user']['id'];
$db->query("SELECT * FROM tenant_accounts WHERE id = :id");
$db->bind(':id', $tenant_id);
$tenant_account = $db->single();

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

// Get all payment records if tenant has active booking
if($active_booking && isset($active_booking['tenant_record_id'])) {
    // Pagination settings
    $records_per_page = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $records_per_page;
    
    // Get payment count
    $db->query("SELECT COUNT(*) as count FROM payments WHERE tenant_id = :tenant_id");
    $db->bind(':tenant_id', $active_booking['tenant_record_id']);
    $total_records = $db->single()['count'];
    $total_pages = ceil($total_records / $records_per_page);
    
    // Get payments with pagination
    $db->query("SELECT * FROM payments WHERE tenant_id = :tenant_id 
                ORDER BY date_created DESC LIMIT :offset, :limit");
    $db->bind(':tenant_id', $active_booking['tenant_record_id']);
    $db->bind(':offset', $offset, PDO::PARAM_INT);
    $db->bind(':limit', $records_per_page, PDO::PARAM_INT);
    $payments = $db->resultSet();
    
    // Calculate rental summary
    $db->query("SELECT SUM(amount) as total_paid FROM payments 
                WHERE tenant_id = :tenant_id AND status = 1");
    $db->bind(':tenant_id', $active_booking['tenant_record_id']);
    $total_paid = $db->single()['total_paid'] ?: 0;
    
    $db->query("SELECT COUNT(*) as count FROM payments 
                WHERE tenant_id = :tenant_id AND status = 1");
    $db->bind(':tenant_id', $active_booking['tenant_record_id']);
    $successful_payments = $db->single()['count'];
    
    $db->query("SELECT COUNT(*) as count FROM payments 
                WHERE tenant_id = :tenant_id AND status = 0");
    $db->bind(':tenant_id', $active_booking['tenant_record_id']);
    $pending_payments = $db->single()['count'];
    
    // Calculate monthly rent
    $monthly_rent = $active_booking['price'];
    
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
        $payment_due_date = $next_payment_date->format('Y-m-d');
    }
}

// Payment status array for display
$payment_status = array(
    0 => array('text' => 'Pending', 'class' => 'secondary'),
    1 => array('text' => 'Approved', 'class' => 'success'),
    2 => array('text' => 'Rejected', 'class' => 'danger')
);

// Payment method array for display
$payment_methods = array(
    'card' => 'Credit/Debit Card',
    'upi' => 'UPI Payment',
    'bank' => 'Bank Transfer',
    'cash' => 'Cash Payment'
);
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<!-- Payment History Section -->
<section class="py-5">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-light py-2 px-3">
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/tenant/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Payment History</li>
            </ol>
        </nav>
        
        <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success']; 
            unset($_SESSION['success']);
            ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error']; 
            unset($_SESSION['error']);
            ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        
        <?php if($active_booking && isset($active_booking['tenant_record_id'])): ?>
        <div class="row">
            <!-- Payment Summary Cards -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-money-bill-wave mr-2"></i>Payment Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <img src="<?php echo BASE_URL . '/' . $active_booking['image_path']; ?>" alt="<?php echo $active_booking['house_no']; ?>" class="img-fluid rounded mb-3" style="max-height: 150px;">
                            <h5><?php echo $active_booking['house_no']; ?></h5>
                            <p class="badge badge-primary"><?php echo $active_booking['category']; ?></p>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Monthly Rent:</span>
                                <span class="font-weight-bold">₹<?php echo number_format($monthly_rent, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Move-in Date:</span>
                                <span><?php echo date('M d, Y', strtotime($active_booking['date_in'])); ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Next Payment:</span>
                                <span>
                                    <?php 
                                    if(isset($payment_due_date)) {
                                        echo date('M d, Y', strtotime($payment_due_date));
                                        
                                        if($days_until_payment <= 0) {
                                            echo ' <span class="badge badge-danger">Due Now</span>';
                                        } elseif($days_until_payment <= 7) {
                                            echo ' <span class="badge badge-warning">In ' . $days_until_payment . ' days</span>';
                                        }
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="summary-stats">
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <div class="summary-item bg-success-light p-3 rounded text-center">
                                        <h3 class="mb-1">₹<?php echo number_format($total_paid, 2); ?></h3>
                                        <p class="mb-0 small">Total Paid</p>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="summary-item bg-info-light p-3 rounded text-center">
                                        <h3 class="mb-1"><?php echo $successful_payments; ?></h3>
                                        <p class="mb-0 small">Successful Payments</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="summary-item bg-warning-light p-3 rounded text-center">
                                        <h3 class="mb-1"><?php echo $pending_payments; ?></h3>
                                        <p class="mb-0 small">Pending Payments</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="summary-item bg-primary-light p-3 rounded text-center">
                                        <h3 class="mb-1"><?php echo $total_records; ?></h3>
                                        <p class="mb-0 small">Total Transactions</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="<?php echo BASE_URL; ?>/tenant/make_payment.php" class="btn btn-success">
                                <i class="fas fa-money-bill-wave mr-2"></i>Make a Payment
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payment History Table -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-history mr-2"></i>Payment History</h5>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="exportDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-download mr-1"></i> Export
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="exportDropdown">
                                <a class="dropdown-item" href="#"><i class="far fa-file-pdf mr-2"></i>PDF</a>
                                <a class="dropdown-item" href="#"><i class="far fa-file-excel mr-2"></i>Excel</a>
                                <a class="dropdown-item" href="#"><i class="far fa-file-alt mr-2"></i>CSV</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if(empty($payments)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-file-invoice-dollar fa-4x text-muted mb-3"></i>
                            <h5>No Payment Records Found</h5>
                            <p>You haven't made any payments yet.</p>
                            <a href="<?php echo BASE_URL; ?>/tenant/make_payment.php" class="btn btn-primary">
                                <i class="fas fa-money-bill-wave mr-2"></i>Make Your First Payment
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Invoice</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($payments as $payment): ?>
                                    <tr>
                                        <td>
                                            <span class="font-weight-bold"><?php echo $payment['invoice']; ?></span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                        <td class="font-weight-bold">₹<?php echo number_format($payment['amount'], 2); ?></td>
                                        <td>
                                            <?php 
                                            echo isset($payment_methods[$payment['payment_method']]) ? 
                                                $payment_methods[$payment['payment_method']] : 
                                                ucfirst($payment['payment_method']); 
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $payment_status[$payment['status']]['class']; ?>">
                                                <?php echo $payment_status[$payment['status']]['text']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary view-payment" 
                                                    data-toggle="modal" data-target="#paymentModal" 
                                                    data-invoice="<?php echo $payment['invoice']; ?>"
                                                    data-date="<?php echo date('M d, Y', strtotime($payment['payment_date'])); ?>"
                                                    data-amount="<?php echo number_format($payment['amount'], 2); ?>"
                                                    data-method="<?php echo isset($payment_methods[$payment['payment_method']]) ? 
                                                        $payment_methods[$payment['payment_method']] : 
                                                        ucfirst($payment['payment_method']); ?>"
                                                    data-status="<?php echo $payment_status[$payment['status']]['text']; ?>"
                                                    data-status-class="<?php echo $payment_status[$payment['status']]['class']; ?>"
                                                    data-transaction="<?php echo $payment['transaction_id']; ?>"
                                                    data-notes="<?php echo $payment['notes']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if($payment['status'] == 1): ?>
                                            <a href="#" class="btn btn-sm btn-outline-success" title="Download Receipt">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo ($page <= 1) ? '#' : BASE_URL . '/tenant/payments.php?page=' . ($page - 1); ?>" tabindex="-1">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo BASE_URL . '/tenant/payments.php?page=' . $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo ($page >= $total_pages) ? '#' : BASE_URL . '/tenant/payments.php?page=' . ($page + 1); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Payment Tips Card -->
                <div class="card shadow mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Payment Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-question-circle mr-2"></i>Payment Status</h6>
                                <ul class="list-unstyled payment-info-list">
                                    <li><span class="badge badge-success mr-2">Approved</span> Payment has been verified and credited.</li>
                                    <li><span class="badge badge-secondary mr-2">Pending</span> Payment is being processed or awaiting verification.</li>
                                    <li><span class="badge badge-danger mr-2">Rejected</span> Payment could not be processed. Please contact support.</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-calendar-alt mr-2"></i>Payment Schedule</h6>
                                <p>Rent payments are due on the same day of each month as your move-in date. Late fees may apply after a 5-day grace period.</p>
                                <p>For any payment issues or concerns, please contact our finance department at <a href="mailto:finance@homerental.com">finance@homerental.com</a> or call <a href="tel:+1800555234">1-800-555-234</a>.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Payment Details Modal -->
        <div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="paymentModalLabel">Payment Details</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row payment-details">
                            <div class="col-12 text-center mb-3">
                                <div class="invoice-icon bg-primary-light rounded-circle d-inline-block mb-2">
                                    <i class="fas fa-file-invoice-dollar text-primary"></i>
                                </div>
                                <h4 class="payment-invoice">INV-20220512-1234</h4>
                                <span class="badge badge-pill badge-success payment-status">Approved</span>
                            </div>
                            
                            <div class="col-12">
                                <table class="table table-striped table-sm">
                                    <tr>
                                        <th>Property:</th>
                                        <td><?php echo $active_booking['house_no']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Payment Date:</th>
                                        <td class="payment-date">May 12, 2022</td>
                                    </tr>
                                    <tr>
                                        <th>Amount:</th>
                                        <td class="payment-amount">₹15,000.00</td>
                                    </tr>
                                    <tr>
                                        <th>Payment Method:</th>
                                        <td class="payment-method">Credit/Debit Card</td>
                                    </tr>
                                    <tr>
                                        <th>Transaction ID:</th>
                                        <td class="payment-transaction">TXID123456789</td>
                                    </tr>
                                    <tr>
                                        <th>Notes:</th>
                                        <td class="payment-notes">Monthly rent payment for May 2022</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary download-receipt">Download Receipt</button>
                    </div>
                </div>
            </div>
        </div>
        
        <?php else: ?>
        <!-- No Active Rental Message -->
        <div class="card shadow">
            <div class="card-body text-center py-5">
                <i class="fas fa-home fa-4x text-muted mb-3"></i>
                <h4>No Active Rental Found</h4>
                <p>You need an active rental to access payment history.</p>
                <a href="<?php echo BASE_URL; ?>/houses.php" class="btn btn-primary mt-2">
                    <i class="fas fa-search mr-2"></i>Browse Properties
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Custom CSS for this page -->
<style>
    .bg-success-light {
        background-color: rgba(40, 167, 69, 0.1);
    }
    
    .bg-info-light {
        background-color: rgba(23, 162, 184, 0.1);
    }
    
    .bg-warning-light {
        background-color: rgba(255, 193, 7, 0.1);
    }
    
    .bg-primary-light {
        background-color: rgba(0, 123, 255, 0.1);
    }
    
    .summary-item h3 {
        color: #444;
        font-size: 1.5rem;
    }
    
    .payment-info-list li {
        margin-bottom: 8px;
    }
    
    .invoice-icon {
        width: 70px;
        height: 70px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .invoice-icon i {
        font-size: 30px;
    }
</style>

<!-- Payment Modal Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle view payment modal
    const viewButtons = document.querySelectorAll('.view-payment');
    if(viewButtons) {
        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                const modal = document.getElementById('paymentModal');
                const invoice = this.getAttribute('data-invoice');
                const date = this.getAttribute('data-date');
                const amount = this.getAttribute('data-amount');
                const method = this.getAttribute('data-method');
                const status = this.getAttribute('data-status');
                const statusClass = this.getAttribute('data-status-class');
                const transaction = this.getAttribute('data-transaction') || 'N/A';
                const notes = this.getAttribute('data-notes') || 'No additional notes';
                
                // Update modal content
                modal.querySelector('.payment-invoice').textContent = invoice;
                modal.querySelector('.payment-date').textContent = date;
                modal.querySelector('.payment-amount').textContent = '₹' + amount;
                modal.querySelector('.payment-method').textContent = method;
                modal.querySelector('.payment-transaction').textContent = transaction;
                modal.querySelector('.payment-notes').textContent = notes;
                
                // Update status badge
                const statusBadge = modal.querySelector('.payment-status');
                statusBadge.textContent = status;
                statusBadge.className = 'badge badge-pill badge-' + statusClass + ' payment-status';
                
                // Show/hide download receipt button based on payment status
                const downloadButton = modal.querySelector('.download-receipt');
                if(status === 'Approved') {
                    downloadButton.style.display = 'block';
                } else {
                    downloadButton.style.display = 'none';
                }
            });
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?> 