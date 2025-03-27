<?php
/**
 * House Rental Management System
 * Tenant Payment Page
 */

// Include initialization file
require_once '../includes/init.php';

// Check if user is logged in as tenant
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'tenant') {
    redirect(BASE_URL . '/tenant/login.php');
}

// Page title
$page_title = 'Make Payment';

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

// If no active booking, redirect to dashboard
if(!$active_booking || !isset($active_booking['tenant_record_id'])) {
    $_SESSION['error'] = "You don't have an active rental to make payments for.";
    redirect(BASE_URL . '/tenant/dashboard.php');
}

// Calculate payment information
$monthly_rent = $active_booking['price'];
$tenant_record_id = $active_booking['tenant_record_id'];

// Check for last payment
$db->query("SELECT * FROM payments WHERE tenant_id = :tenant_id ORDER BY date_created DESC LIMIT 1");
$db->bind(':tenant_id', $tenant_record_id);
$last_payment = $db->single();

// Generate invoice number
$invoice_prefix = 'INV';
$date_code = date('Ymd');
$random_digits = mt_rand(1000, 9999);
$invoice_number = $invoice_prefix . '-' . $date_code . '-' . $random_digits;

// Calculate next payment date
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

// Payment methods
$payment_methods = array(
    'card' => 'Credit/Debit Card',
    'upi' => 'UPI Payment',
    'bank' => 'Bank Transfer',
    'cash' => 'Cash (Pay at Office)'
);

// Handle payment submission
$errors = array();
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate payment information
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
    $amount = isset($_POST['amount']) ? filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT) : 0;
    $transaction_id = isset($_POST['transaction_id']) ? sanitize($_POST['transaction_id']) : '';
    $notes = isset($_POST['notes']) ? sanitize($_POST['notes']) : '';
    
    // Validation
    if(!in_array($payment_method, array_keys($payment_methods))) {
        $errors[] = "Please select a valid payment method.";
    }
    
    if($amount <= 0 || $amount < $monthly_rent) {
        $errors[] = "Payment amount must be at least ₹" . number_format($monthly_rent, 2);
    }
    
    if($payment_method != 'cash' && empty($transaction_id)) {
        $errors[] = "Transaction ID is required for online payments.";
    }
    
    // If no errors, process payment
    if(empty($errors)) {
        try {
            // Insert payment record
            $db->query("INSERT INTO payments (tenant_id, invoice, amount, payment_method, transaction_id, payment_date, notes, status) 
                       VALUES (:tenant_id, :invoice, :amount, :payment_method, :transaction_id, :payment_date, :notes, :status)");
            $db->bind(':tenant_id', $tenant_record_id);
            $db->bind(':invoice', $invoice_number);
            $db->bind(':amount', $amount);
            $db->bind(':payment_method', $payment_method);
            $db->bind(':transaction_id', $transaction_id);
            $db->bind(':payment_date', date('Y-m-d H:i:s'));
            $db->bind(':notes', $notes);
            $db->bind(':status', ($payment_method == 'cash') ? 0 : 1); // Cash payments require verification
            
            $db->execute();
            
            // Set success message and redirect
            $_SESSION['success'] = "Your payment of ₹" . number_format($amount, 2) . " has been " . 
                                  (($payment_method == 'cash') ? 'recorded and pending verification.' : 'successfully processed.');
            redirect(BASE_URL . '/tenant/payments.php');
        } catch(Exception $e) {
            $errors[] = "An error occurred while processing your payment. Please try again.";
            error_log("Payment error: " . $e->getMessage());
        }
    }
}
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<!-- Payment Section -->
<section class="py-5">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-light py-2 px-3">
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/tenant/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Make Payment</li>
            </ol>
        </nav>
        
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-money-bill-wave mr-2"></i>Make a Payment</h4>
                    </div>
                    <div class="card-body">
                        
                        <?php if(!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(!empty($success)): ?>
                        <div class="alert alert-success">
                            <?php echo $success; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Property Details Card -->
                        <div class="card mb-4 border-primary">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Property Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3 mb-md-0">
                                        <img src="<?php echo BASE_URL . '/' . $active_booking['image_path']; ?>" alt="<?php echo $active_booking['house_no']; ?>" class="img-fluid rounded">
                                    </div>
                                    <div class="col-md-9">
                                        <h5><?php echo $active_booking['house_no']; ?></h5>
                                        <p class="badge badge-primary"><?php echo $active_booking['category']; ?></p>
                                        <p class="text-muted small"><?php echo $active_booking['description']; ?></p>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Monthly Rent:</strong> ₹<?php echo number_format($monthly_rent, 2); ?></p>
                                                <p><strong>Move-in Date:</strong> <?php echo date('M d, Y', strtotime($active_booking['date_in'])); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Payment Due:</strong> <?php echo isset($payment_due_date) ? date('M d, Y', strtotime($payment_due_date)) : 'N/A'; ?></p>
                                                <?php if(isset($days_until_payment)): ?>
                                                <p>
                                                    <strong>Status:</strong> 
                                                    <?php if($days_until_payment <= 0): ?>
                                                    <span class="badge badge-danger">Due Now</span>
                                                    <?php elseif($days_until_payment <= 7): ?>
                                                    <span class="badge badge-warning">Due in <?php echo $days_until_payment; ?> days</span>
                                                    <?php else: ?>
                                                    <span class="badge badge-success">Upcoming</span>
                                                    <?php endif; ?>
                                                </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment Form -->
                        <form id="paymentForm" method="post" action="">
                            <div class="form-group">
                                <label for="invoice">Invoice Number</label>
                                <input type="text" class="form-control" id="invoice" value="<?php echo $invoice_number; ?>" readonly>
                                <small class="text-muted">This is your unique payment reference</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="amount">Payment Amount (₹)</label>
                                <input type="number" class="form-control" id="amount" name="amount" min="<?php echo $monthly_rent; ?>" step="0.01" value="<?php echo $monthly_rent; ?>" required>
                                <small class="text-muted">Minimum payment: ₹<?php echo number_format($monthly_rent, 2); ?></small>
                            </div>
                            
                            <div class="form-group">
                                <label>Payment Method</label>
                                <?php foreach($payment_methods as $key => $method): ?>
                                <div class="custom-control custom-radio mb-2">
                                    <input type="radio" id="payment_method_<?php echo $key; ?>" name="payment_method" class="custom-control-input payment-method" value="<?php echo $key; ?>" <?php echo ($key == 'card') ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="payment_method_<?php echo $key; ?>"><?php echo $method; ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div id="onlinePaymentDetails">
                                <div class="card mb-3 payment-info-card" id="card_info">
                                    <div class="card-body">
                                        <h6 class="mb-3">Credit/Debit Card Payment</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="card_number">Card Number</label>
                                                    <input type="text" class="form-control" id="card_number" placeholder="XXXX XXXX XXXX XXXX">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="card_name">Cardholder Name</label>
                                                    <input type="text" class="form-control" id="card_name">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="card_expiry">Expiry Date</label>
                                                    <input type="text" class="form-control" id="card_expiry" placeholder="MM/YY">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="card_cvv">CVV</label>
                                                    <input type="text" class="form-control" id="card_cvv" placeholder="XXX">
                                                </div>
                                            </div>
                                        </div>
                                        <small class="text-muted">For demonstration purposes only. No actual payment will be processed.</small>
                                    </div>
                                </div>
                                
                                <div class="card mb-3 payment-info-card d-none" id="upi_info">
                                    <div class="card-body">
                                        <h6 class="mb-3">UPI Payment</h6>
                                        <p>Please make payment to our UPI ID: <strong>homerental@upi</strong></p>
                                        <div class="text-center mb-3">
                                            <img src="<?php echo IMG_PATH; ?>/qr-code-sample.png" alt="QR Code" class="img-fluid" style="max-width: 200px;">
                                        </div>
                                        <small class="text-muted">After completing the payment, please enter the UPI transaction ID below.</small>
                                    </div>
                                </div>
                                
                                <div class="card mb-3 payment-info-card d-none" id="bank_info">
                                    <div class="card-body">
                                        <h6 class="mb-3">Bank Transfer Details</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <tr>
                                                    <th>Bank Name</th>
                                                    <td>Sample Bank</td>
                                                </tr>
                                                <tr>
                                                    <th>Account Name</th>
                                                    <td>House Rental System</td>
                                                </tr>
                                                <tr>
                                                    <th>Account Number</th>
                                                    <td>1234567890123</td>
                                                </tr>
                                                <tr>
                                                    <th>IFSC Code</th>
                                                    <td>SAMP0001234</td>
                                                </tr>
                                                <tr>
                                                    <th>Branch</th>
                                                    <td>Main Branch</td>
                                                </tr>
                                            </table>
                                        </div>
                                        <small class="text-muted">After transferring, please enter the transaction reference number below.</small>
                                    </div>
                                </div>
                                
                                <div class="card mb-3 payment-info-card d-none" id="cash_info">
                                    <div class="card-body">
                                        <h6 class="mb-3">Cash Payment</h6>
                                        <p>You can make a cash payment at our office during business hours:</p>
                                        <p><strong>Address:</strong> House Rental Office, 123 Main Street, City</p>
                                        <p><strong>Hours:</strong> Monday to Friday, 9:00 AM - 5:00 PM</p>
                                        <p><strong>Note:</strong> Please bring your tenant ID and invoice number with you.</p>
                                        <small class="text-muted">Your payment will be marked as "Pending" until verified by our staff.</small>
                                    </div>
                                </div>
                                
                                <div class="form-group transaction-id">
                                    <label for="transaction_id">Transaction ID/Reference</label>
                                    <input type="text" class="form-control" id="transaction_id" name="transaction_id" placeholder="Enter transaction reference number">
                                    <small class="text-muted">For card payments, enter the last 4 digits of your card number.</small>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="notes">Payment Notes (Optional)</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Any additional information about this payment"></textarea>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I confirm that the information provided is correct and authorize this payment.
                                </label>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-money-bill-wave mr-2"></i>Complete Payment
                                </button>
                                <a href="<?php echo BASE_URL; ?>/tenant/dashboard.php" class="btn btn-outline-secondary btn-lg ml-2">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Custom CSS for this page -->
<style>
    .payment-info-card {
        border-left: 4px solid #4e73df;
        background-color: #f8f9fc;
    }
    
    .custom-control-label {
        cursor: pointer;
    }
</style>

<!-- Payment Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Payment method selection
    const paymentMethods = document.querySelectorAll('.payment-method');
    const paymentInfoCards = document.querySelectorAll('.payment-info-card');
    const transactionIdField = document.querySelector('.transaction-id');
    
    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            // Hide all payment info cards
            paymentInfoCards.forEach(card => {
                card.classList.add('d-none');
            });
            
            // Show selected payment method info
            const selectedMethod = this.value;
            document.getElementById(selectedMethod + '_info').classList.remove('d-none');
            
            // Show/hide transaction ID field based on payment method
            if(selectedMethod === 'cash') {
                transactionIdField.style.display = 'none';
            } else {
                transactionIdField.style.display = 'block';
                
                // Update transaction ID field placeholder based on payment method
                const transactionIdInput = document.getElementById('transaction_id');
                const transactionIdHelp = transactionIdInput.nextElementSibling;
                
                if(selectedMethod === 'card') {
                    transactionIdHelp.textContent = 'For card payments, enter the last 4 digits of your card number.';
                } else if(selectedMethod === 'upi') {
                    transactionIdHelp.textContent = 'Enter the UPI transaction reference ID.';
                } else if(selectedMethod === 'bank') {
                    transactionIdHelp.textContent = 'Enter the bank transaction reference number.';
                }
            }
        });
    });
    
    // Trigger change event on the checked payment method
    const checkedMethod = document.querySelector('.payment-method:checked');
    if(checkedMethod) {
        checkedMethod.dispatchEvent(new Event('change'));
    }
    
    // Format card inputs
    const cardNumberInput = document.getElementById('card_number');
    if(cardNumberInput) {
        cardNumberInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '').substring(0, 16);
            // Add space after every 4 digits
            if(value.length > 4) {
                value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
            }
            e.target.value = value;
        });
    }
    
    const cardExpiryInput = document.getElementById('card_expiry');
    if(cardExpiryInput) {
        cardExpiryInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '').substring(0, 4);
            if(value.length > 2) {
                value = value.substring(0, 2) + '/' + value.substring(2);
            }
            e.target.value = value;
        });
    }
    
    const cardCvvInput = document.getElementById('card_cvv');
    if(cardCvvInput) {
        cardCvvInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, 3);
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>