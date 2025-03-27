<?php
/**
 * House Rental Management System
 * Tenant Maintenance Request
 */

// Include initialization file
require_once '../includes/init.php';

// Check if user is logged in as tenant
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'tenant') {
    redirect(BASE_URL . '/tenant/login.php');
}

// Page title
$page_title = 'Maintenance Requests';

// Get tenant details
$tenant_id = $_SESSION['rental_user']['id'];
$db->query("SELECT * FROM tenant_accounts WHERE id = :id");
$db->bind(':id', $tenant_id);
$tenant_account = $db->single();

// Check if tenant has active booking/tenancy
$db->query("SELECT br.*, h.house_no, h.description, h.price, h.id as house_id,
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
    $_SESSION['error'] = "You don't have an active rental to submit maintenance requests for.";
    redirect(BASE_URL . '/tenant/dashboard.php');
}

// Get previous maintenance requests
$db->query("SELECT * FROM maintenance_requests 
           WHERE tenant_id = :tenant_id AND house_id = :house_id 
           ORDER BY date_created DESC");
$db->bind(':tenant_id', $tenant_id);
$db->bind(':house_id', $active_booking['house_id']);
$previous_requests = $db->resultSet();

// Maintenance request types
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

// Priority levels
$priority_levels = array(
    'low' => 'Low - Not Urgent',
    'medium' => 'Medium - Needs Attention',
    'high' => 'High - Urgent Issue',
    'emergency' => 'Emergency - Immediate Action Required'
);

// Handle form submission
$errors = array();
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $request_type = isset($_POST['request_type']) ? sanitize($_POST['request_type']) : '';
    $priority = isset($_POST['priority']) ? sanitize($_POST['priority']) : '';
    $issue_description = isset($_POST['issue_description']) ? sanitize($_POST['issue_description']) : '';
    $preferred_date = isset($_POST['preferred_date']) ? sanitize($_POST['preferred_date']) : '';
    $access_permission = isset($_POST['access_permission']) ? 1 : 0;
    $additional_notes = isset($_POST['additional_notes']) ? sanitize($_POST['additional_notes']) : '';
    
    // Validation
    if(!in_array($request_type, array_keys($request_types))) {
        $errors[] = "Please select a valid request type.";
    }
    
    if(!in_array($priority, array_keys($priority_levels))) {
        $errors[] = "Please select a valid priority level.";
    }
    
    if(empty($issue_description)) {
        $errors[] = "Please provide a description of the issue.";
    }
    
    // Validate preferred date (if provided)
    if(!empty($preferred_date)) {
        $date_now = new DateTime();
        $date_selected = new DateTime($preferred_date);
        
        if($date_selected < $date_now) {
            $errors[] = "Preferred date cannot be in the past.";
        }
    }
    
    // If no errors, process maintenance request
    if(empty($errors)) {
        try {
            // Generate unique request ID
            $request_prefix = 'MR';
            $date_code = date('Ymd');
            $random_digits = mt_rand(1000, 9999);
            $request_id = $request_prefix . '-' . $date_code . '-' . $random_digits;
            
            // Insert maintenance request
            $db->query("INSERT INTO maintenance_requests (request_id, tenant_id, house_id, request_type, priority, 
                       issue_description, preferred_date, access_permission, additional_notes, status) 
                       VALUES (:request_id, :tenant_id, :house_id, :request_type, :priority, 
                       :issue_description, :preferred_date, :access_permission, :additional_notes, :status)");
            
            $db->bind(':request_id', $request_id);
            $db->bind(':tenant_id', $tenant_id);
            $db->bind(':house_id', $active_booking['house_id']);
            $db->bind(':request_type', $request_type);
            $db->bind(':priority', $priority);
            $db->bind(':issue_description', $issue_description);
            $db->bind(':preferred_date', !empty($preferred_date) ? $preferred_date : null);
            $db->bind(':access_permission', $access_permission);
            $db->bind(':additional_notes', $additional_notes);
            $db->bind(':status', 'pending'); // Initial status is pending
            
            $db->execute();
            
            // Set success message
            $_SESSION['success'] = "Your maintenance request has been submitted successfully. Your request ID is: $request_id";
            redirect(BASE_URL . '/tenant/maintenance.php');
        } catch(Exception $e) {
            $errors[] = "An error occurred while submitting your request. Please try again.";
            error_log("Maintenance request error: " . $e->getMessage());
        }
    }
}
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<!-- Maintenance Request Section -->
<section class="py-5">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-light py-2 px-3">
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/tenant/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Maintenance Requests</li>
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
        
        <div class="row">
            <!-- New Maintenance Request Form -->
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-tools mr-2"></i>Submit a Maintenance Request</h4>
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
                        
                        <!-- Property Details -->
                        <div class="property-summary mb-4">
                            <div class="row">
                                <div class="col-md-3 mb-3 mb-md-0">
                                    <img src="<?php echo BASE_URL . '/' . $active_booking['image_path']; ?>" alt="<?php echo $active_booking['house_no']; ?>" class="img-fluid rounded">
                                </div>
                                <div class="col-md-9">
                                    <h5><?php echo $active_booking['house_no']; ?></h5>
                                    <p class="badge badge-primary"><?php echo $active_booking['category']; ?></p>
                                    <p class="text-muted small mb-0"><?php echo $active_booking['description']; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <form method="post" action="" id="maintenanceForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="request_type">Type of Issue</label>
                                        <select class="form-control" id="request_type" name="request_type" required>
                                            <option value="">Select Issue Type</option>
                                            <?php foreach($request_types as $key => $type): ?>
                                            <option value="<?php echo $key; ?>"><?php echo $type; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="priority">Priority Level</label>
                                        <select class="form-control" id="priority" name="priority" required>
                                            <option value="">Select Priority</option>
                                            <?php foreach($priority_levels as $key => $level): ?>
                                            <option value="<?php echo $key; ?>"><?php echo $level; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="form-text text-muted">Please select priority based on urgency and safety impact.</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="issue_description">Description of Issue</label>
                                <textarea class="form-control" id="issue_description" name="issue_description" rows="4" required placeholder="Please provide details about the issue, when it started, and any relevant information that could help resolve it."></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="preferred_date">Preferred Service Date (Optional)</label>
                                        <input type="date" class="form-control" id="preferred_date" name="preferred_date" min="<?php echo date('Y-m-d'); ?>">
                                        <small class="form-text text-muted">We'll try to accommodate your preferred date if possible.</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="d-block">Access Permission</label>
                                        <div class="custom-control custom-switch mt-2">
                                            <input type="checkbox" class="custom-control-input" id="access_permission" name="access_permission">
                                            <label class="custom-control-label" for="access_permission">I authorize staff to enter my unit in my absence if needed for this repair.</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="additional_notes">Additional Notes (Optional)</label>
                                <textarea class="form-control" id="additional_notes" name="additional_notes" rows="2" placeholder="Any additional information or special instructions."></textarea>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I confirm that the information provided is accurate to the best of my knowledge.
                                </label>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane mr-2"></i>Submit Request
                                </button>
                                <a href="<?php echo BASE_URL; ?>/tenant/dashboard.php" class="btn btn-outline-secondary ml-2">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar with Previous Requests -->
            <div class="col-lg-4">
                <div class="card shadow">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-history mr-2"></i>Previous Requests</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if(empty($previous_requests)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                            <p>You haven't submitted any maintenance requests yet.</p>
                        </div>
                        <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach($previous_requests as $request): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-1">
                                        <?php echo $request_types[$request['request_type']]; ?>
                                        <span class="badge badge-<?php 
                                        switch($request['priority']) {
                                            case 'low': echo 'info'; break;
                                            case 'medium': echo 'primary'; break;
                                            case 'high': echo 'warning'; break;
                                            case 'emergency': echo 'danger'; break;
                                        }
                                        ?>">
                                            <?php echo ucfirst($request['priority']); ?>
                                        </span>
                                    </h6>
                                    <small class="text-muted"><?php echo date('M d, Y', strtotime($request['date_created'])); ?></small>
                                </div>
                                <p class="mb-1 small text-truncate"><?php echo $request['issue_description']; ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted"><?php echo $request['request_id']; ?></small>
                                    <span class="badge badge-<?php 
                                    switch($request['status']) {
                                        case 'pending': echo 'secondary'; break;
                                        case 'scheduled': echo 'info'; break;
                                        case 'in_progress': echo 'primary'; break;
                                        case 'completed': echo 'success'; break;
                                        case 'cancelled': echo 'danger'; break;
                                    }
                                    ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $request['status'])); ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Status Legend:</span>
                            <span class="badge badge-secondary">Pending</span>
                            <span class="badge badge-info">Scheduled</span>
                            <span class="badge badge-primary">In Progress</span>
                            <span class="badge badge-success">Completed</span>
                        </div>
                    </div>
                </div>
                
                <!-- Maintenance Tips -->
                <div class="card shadow mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-lightbulb mr-2"></i>Maintenance Tips</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <strong>Clogged Drains?</strong> Try using a plunger before reporting.
                            </li>
                            <li class="list-group-item">
                                <strong>Power Outage?</strong> Check your circuit breakers first.
                            </li>
                            <li class="list-group-item">
                                <strong>Leaking Faucet?</strong> Make sure the handle is fully turned off.
                            </li>
                            <li class="list-group-item">
                                <strong>Non-working Appliance?</strong> Ensure it's properly plugged in.
                            </li>
                            <li class="list-group-item">
                                <strong>Emergency?</strong> For water leaks, gas smells, or electrical hazards, call the emergency maintenance line at <a href="tel:+1800555123">1-800-555-123</a> immediately.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Custom CSS for this page -->
<style>
    .property-summary {
        border-radius: 10px;
        background-color: #f8f9fc;
        padding: 15px;
        border: 1px solid #e3e6f0;
    }
    
    .card-header h5, .card-header h4 {
        font-weight: 600;
    }
    
    .list-group-item:hover {
        background-color: #f8f9fc;
    }
</style>

<?php include '../includes/footer.php'; ?> 