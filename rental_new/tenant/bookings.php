<?php
/**
 * House Rental Management System
 * Tenant Booking Requests
 */

// Include initialization file
require_once '../includes/init.php';

// Check if user is logged in as tenant
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'tenant') {
    redirect(BASE_URL . '/tenant/login.php');
}

// Page title
$page_title = 'My Booking Requests';

// Get tenant details
$tenant_id = $_SESSION['rental_user']['id'];
$db->query("SELECT * FROM tenant_accounts WHERE id = :id");
$db->bind(':id', $tenant_id);
$tenant_account = $db->single();

// Fetch all booking requests for this tenant
$house = new House();
$booking_requests = $house->getTenantBookingRequests($tenant_id);

// Count bookings by status
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;
$total_count = count($booking_requests);

foreach($booking_requests as $request) {
    switch($request['status']) {
        case 0:
            $pending_count++;
            break;
        case 1:
            $approved_count++;
            break;
        case 2:
            $rejected_count++;
            break;
    }
}

// Handle booking cancellation
if(isset($_GET['cancel']) && !empty($_GET['cancel'])) {
    $booking_id = (int)$_GET['cancel'];
    
    // Verify the booking belongs to this tenant and is in pending status
    $db->query("SELECT * FROM booking_requests WHERE id = :id AND tenant_id = :tenant_id AND status = 0");
    $db->bind(':id', $booking_id);
    $db->bind(':tenant_id', $tenant_id);
    $booking = $db->single();
    
    if($booking) {
        // Update booking status to cancelled (3)
        $db->query("UPDATE booking_requests SET status = 3, date_updated = NOW() WHERE id = :id");
        $db->bind(':id', $booking_id);
        
        if($db->execute()) {
            $_SESSION['success'] = "Your booking request has been successfully cancelled.";
        } else {
            $_SESSION['error'] = "There was a problem cancelling your booking request. Please try again.";
        }
        
        redirect(BASE_URL . '/tenant/bookings.php');
    } else {
        $_SESSION['error'] = "Invalid booking request or you don't have permission to cancel it.";
        redirect(BASE_URL . '/tenant/bookings.php');
    }
}

// Booking status labels and classes
$status_labels = array(
    0 => array('label' => 'Pending', 'class' => 'warning'),
    1 => array('label' => 'Approved', 'class' => 'success'),
    2 => array('label' => 'Rejected', 'class' => 'danger'),
    3 => array('label' => 'Cancelled', 'class' => 'secondary')
);
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<!-- Booking Requests Section -->
<section class="py-5">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-light py-2 px-3">
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/tenant/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Booking Requests</li>
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
        
        <!-- Booking Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Requests</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_count; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-bookmark fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Pending</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_count; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clock fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Approved</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $approved_count; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                    Rejected/Cancelled</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $rejected_count; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Booking Requests Table -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">My Booking Requests</h6>
                <a href="<?php echo BASE_URL; ?>/houses.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-search mr-1"></i> Browse More Properties
                </a>
            </div>
            <div class="card-body">
                <?php if(empty($booking_requests)): ?>
                <div class="text-center py-5">
                    <img src="<?php echo IMG_PATH; ?>/empty-bookings.svg" alt="No Bookings" class="img-fluid mb-3" style="max-height: 150px;">
                    <h5>No Booking Requests Found</h5>
                    <p>You haven't made any property booking requests yet.</p>
                    <a href="<?php echo BASE_URL; ?>/houses.php" class="btn btn-primary mt-2">
                        <i class="fas fa-search mr-2"></i>Browse Available Properties
                    </a>
                </div>
                <?php else: ?>
                
                <!-- Filter Buttons -->
                <div class="mb-3">
                    <button class="btn btn-sm btn-outline-primary filter-btn active" data-filter="all">All</button>
                    <button class="btn btn-sm btn-outline-warning filter-btn" data-filter="0">Pending</button>
                    <button class="btn btn-sm btn-outline-success filter-btn" data-filter="1">Approved</button>
                    <button class="btn btn-sm btn-outline-danger filter-btn" data-filter="2">Rejected</button>
                    <button class="btn btn-sm btn-outline-secondary filter-btn" data-filter="3">Cancelled</button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover booking-table">
                        <thead class="thead-light">
                            <tr>
                                <th>Property</th>
                                <th>Price/Month</th>
                                <th>Move-in Date</th>
                                <th>Status</th>
                                <th>Request Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($booking_requests as $request): ?>
                            <tr class="booking-row" data-status="<?php echo $request['status']; ?>">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo BASE_URL . '/' . (empty($request['image_path']) ? 'assets/img/house-default.jpg' : $request['image_path']); ?>" 
                                             alt="<?php echo $request['house_no']; ?>" 
                                             class="mr-3 rounded booking-img">
                                        <div>
                                            <strong><?php echo $request['house_no']; ?></strong>
                                            <div class="small text-muted"><?php echo $request['category']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>₹<?php echo number_format($request['price'], 2); ?></td>
                                <td><?php echo date('M d, Y', strtotime($request['move_in_date'])); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $status_labels[$request['status']]['class']; ?>">
                                        <?php echo $status_labels[$request['status']]['label']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($request['date_created'])); ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/house_details.php?id=<?php echo $request['house_id']; ?>" 
                                       class="btn btn-sm btn-outline-primary" title="View Property">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <?php if($request['status'] == 0): ?>
                                    <a href="#" class="btn btn-sm btn-outline-danger cancel-booking" 
                                       data-id="<?php echo $request['id']; ?>"
                                       data-house="<?php echo $request['house_no']; ?>"
                                       title="Cancel Request">
                                        <i class="fas fa-times"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="btn btn-sm btn-outline-info view-booking" 
                                            data-toggle="modal" 
                                            data-target="#bookingModal"
                                            data-id="<?php echo $request['id']; ?>"
                                            data-house="<?php echo $request['house_no']; ?>"
                                            data-image="<?php echo BASE_URL . '/' . (empty($request['image_path']) ? 'assets/img/house-default.jpg' : $request['image_path']); ?>"
                                            data-price="<?php echo number_format($request['price'], 2); ?>"
                                            data-category="<?php echo $request['category']; ?>"
                                            data-movedate="<?php echo date('M d, Y', strtotime($request['move_in_date'])); ?>"
                                            data-requestdate="<?php echo date('M d, Y', strtotime($request['date_created'])); ?>"
                                            data-notes="<?php echo $request['notes']; ?>"
                                            data-status="<?php echo $request['status']; ?>"
                                            data-statuslabel="<?php echo $status_labels[$request['status']]['label']; ?>"
                                            data-statusclass="<?php echo $status_labels[$request['status']]['class']; ?>"
                                            title="View Details">
                                        <i class="fas fa-info-circle"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Booking Process Guide -->
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Booking Process Guide</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center mb-4 mb-md-0">
                        <div class="process-icon bg-primary-light rounded-circle mx-auto mb-3">
                            <i class="fas fa-search text-primary"></i>
                        </div>
                        <h5>1. Browse</h5>
                        <p class="small">Search and select your preferred rental property</p>
                    </div>
                    <div class="col-md-3 text-center mb-4 mb-md-0">
                        <div class="process-icon bg-warning-light rounded-circle mx-auto mb-3">
                            <i class="fas fa-clock text-warning"></i>
                        </div>
                        <h5>2. Request</h5>
                        <p class="small">Submit a booking request with your move-in date</p>
                    </div>
                    <div class="col-md-3 text-center mb-4 mb-md-0">
                        <div class="process-icon bg-success-light rounded-circle mx-auto mb-3">
                            <i class="fas fa-check-circle text-success"></i>
                        </div>
                        <h5>3. Approval</h5>
                        <p class="small">Wait for approval from the property manager</p>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="process-icon bg-info-light rounded-circle mx-auto mb-3">
                            <i class="fas fa-key text-info"></i>
                        </div>
                        <h5>4. Move In</h5>
                        <p class="small">Complete payment and move into your new home</p>
                    </div>
                </div>
                
                <div class="alert alert-info mt-4 mb-0">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Note:</strong> The property manager will review your request and may contact you for additional information. The approval process typically takes 1-3 business days.
                </div>
            </div>
        </div>
    </div>
    
    <!-- Booking Details Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1" role="dialog" aria-labelledby="bookingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="bookingModalLabel">Booking Request Details</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row booking-details">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <img src="" alt="" class="img-fluid rounded w-100 booking-detail-img">
                        </div>
                        <div class="col-md-8">
                            <h4 class="booking-house">House Title</h4>
                            <p class="badge badge-primary booking-category">Category</p>
                            
                            <div class="booking-status-container mb-3">
                                <span class="badge badge-pill badge-success booking-status">Status</span>
                            </div>
                            
                            <table class="table table-sm">
                                <tr>
                                    <th>Request ID:</th>
                                    <td class="booking-id">#123</td>
                                </tr>
                                <tr>
                                    <th>Monthly Rent:</th>
                                    <td class="booking-price">₹15,000.00</td>
                                </tr>
                                <tr>
                                    <th>Move-in Date:</th>
                                    <td class="booking-movedate">Jan 1, 2023</td>
                                </tr>
                                <tr>
                                    <th>Request Date:</th>
                                    <td class="booking-requestdate">Dec 15, 2022</td>
                                </tr>
                                <tr>
                                    <th>Additional Notes:</th>
                                    <td class="booking-notes">Looking for a long-term rental.</td>
                                </tr>
                            </table>
                            
                            <div class="booking-status-message alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                <span>Your booking request is being processed.</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <a href="#" class="btn btn-primary view-property-btn">
                        <i class="fas fa-eye mr-1"></i> View Property
                    </a>
                    <a href="#" class="btn btn-danger cancel-btn d-none">
                        <i class="fas fa-times mr-1"></i> Cancel Request
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cancel Booking Confirmation Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1" role="dialog" aria-labelledby="cancelModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="cancelModalLabel">Confirm Cancellation</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel your booking request for <strong id="cancel-house-name"></strong>?</p>
                    <p>This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">No, Keep Request</button>
                    <a href="#" id="confirm-cancel" class="btn btn-danger">
                        <i class="fas fa-times mr-1"></i> Yes, Cancel Request
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Custom CSS for this page -->
<style>
    .border-left-primary {
        border-left: 4px solid #4e73df !important;
    }
    
    .border-left-success {
        border-left: 4px solid #1cc88a !important;
    }
    
    .border-left-warning {
        border-left: 4px solid #f6c23e !important;
    }
    
    .border-left-danger {
        border-left: 4px solid #e74a3b !important;
    }
    
    .booking-img {
        width: 50px;
        height: 40px;
        object-fit: cover;
    }
    
    .process-icon {
        width: 70px;
        height: 70px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .process-icon i {
        font-size: 25px;
    }
    
    .bg-primary-light {
        background-color: rgba(0, 123, 255, 0.1);
    }
    
    .bg-success-light {
        background-color: rgba(40, 167, 69, 0.1);
    }
    
    .bg-warning-light {
        background-color: rgba(255, 193, 7, 0.1);
    }
    
    .bg-info-light {
        background-color: rgba(23, 162, 184, 0.1);
    }
    
    .filter-btn.active {
        background-color: #4e73df;
        color: white;
    }
</style>

<!-- Booking Page Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter bookings by status
    const filterButtons = document.querySelectorAll('.filter-btn');
    const bookingRows = document.querySelectorAll('.booking-row');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Update active button
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            const filterValue = this.getAttribute('data-filter');
            
            // Show/hide rows based on filter
            bookingRows.forEach(row => {
                if(filterValue === 'all' || row.getAttribute('data-status') === filterValue) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
    
    // Handle view booking detail modal
    const viewButtons = document.querySelectorAll('.view-booking');
    if(viewButtons) {
        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                const modal = document.getElementById('bookingModal');
                const id = this.getAttribute('data-id');
                const house = this.getAttribute('data-house');
                const image = this.getAttribute('data-image');
                const price = this.getAttribute('data-price');
                const category = this.getAttribute('data-category');
                const moveDate = this.getAttribute('data-movedate');
                const requestDate = this.getAttribute('data-requestdate');
                const notes = this.getAttribute('data-notes') || 'No additional notes provided';
                const status = this.getAttribute('data-status');
                const statusLabel = this.getAttribute('data-statuslabel');
                const statusClass = this.getAttribute('data-statusclass');
                
                // Update modal content
                modal.querySelector('.booking-id').textContent = '#' + id;
                modal.querySelector('.booking-house').textContent = house;
                modal.querySelector('.booking-detail-img').src = image;
                modal.querySelector('.booking-detail-img').alt = house;
                modal.querySelector('.booking-price').textContent = '₹' + price;
                modal.querySelector('.booking-category').textContent = category;
                modal.querySelector('.booking-movedate').textContent = moveDate;
                modal.querySelector('.booking-requestdate').textContent = requestDate;
                modal.querySelector('.booking-notes').textContent = notes;
                
                // Update status badge
                const statusBadge = modal.querySelector('.booking-status');
                statusBadge.textContent = statusLabel;
                statusBadge.className = 'badge badge-pill badge-' + statusClass + ' booking-status';
                
                // Update status message
                const statusMessage = modal.querySelector('.booking-status-message span');
                switch(status) {
                    case '0':
                        statusMessage.textContent = 'Your booking request is currently being reviewed. We will notify you once it has been processed.';
                        break;
                    case '1':
                        statusMessage.textContent = 'Congratulations! Your booking request has been approved. You can proceed with the rental process.';
                        modal.querySelector('.booking-status-message').className = 'booking-status-message alert alert-success';
                        break;
                    case '2':
                        statusMessage.textContent = 'We\'re sorry, your booking request was not approved at this time. Please contact us for more information.';
                        modal.querySelector('.booking-status-message').className = 'booking-status-message alert alert-danger';
                        break;
                    case '3':
                        statusMessage.textContent = 'This booking request has been cancelled.';
                        modal.querySelector('.booking-status-message').className = 'booking-status-message alert alert-secondary';
                        break;
                }
                
                // Update view property button link
                modal.querySelector('.view-property-btn').href = BASE_URL + '/house_details.php?id=' + this.getAttribute('data-id');
                
                // Show/hide cancel button based on status
                const cancelBtn = modal.querySelector('.cancel-btn');
                if(status === '0') {
                    cancelBtn.classList.remove('d-none');
                    cancelBtn.href = '#';
                    cancelBtn.setAttribute('data-id', id);
                    cancelBtn.setAttribute('data-house', house);
                } else {
                    cancelBtn.classList.add('d-none');
                }
            });
        });
    }
    
    // Handle cancel booking request
    const cancelButtons = document.querySelectorAll('.cancel-booking');
    if(cancelButtons) {
        cancelButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.getAttribute('data-id');
                const house = this.getAttribute('data-house');
                
                const cancelModal = document.getElementById('cancelModal');
                cancelModal.querySelector('#cancel-house-name').textContent = house;
                cancelModal.querySelector('#confirm-cancel').href = BASE_URL + '/tenant/bookings.php?cancel=' + id;
                
                $('#cancelModal').modal('show');
            });
        });
    }
    
    // Handle modal cancel button
    const modalCancelBtn = document.querySelector('.cancel-btn');
    if(modalCancelBtn) {
        modalCancelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const house = this.getAttribute('data-house');
            
            const cancelModal = document.getElementById('cancelModal');
            cancelModal.querySelector('#cancel-house-name').textContent = house;
            cancelModal.querySelector('#confirm-cancel').href = BASE_URL + '/tenant/bookings.php?cancel=' + id;
            
            $('#bookingModal').modal('hide');
            $('#cancelModal').modal('show');
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?> 