<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * House Rental Management System
 * Admin Houses Management
 */

// Include initialization file
require_once '../includes/init.php';

// Check if user is logged in as admin
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    redirect(BASE_URL . '/admin/login.php');
}

// Page title
$page_title = 'Houses Management';

// Initialize database
$db = new Database();

// Handle form submissions
$success_msg = '';
$error_msg = '';

// Delete house
if(isset($_GET['delete']) && !empty($_GET['delete'])) {
    $house_id = (int)$_GET['delete'];
    
    // Check if house is in use (has bookings or tenants)
    $db->query("SELECT COUNT(*) as count FROM booking_requests WHERE house_id = :house_id");
    $db->bind(':house_id', $house_id);
    $house_usage = $db->single();
    
    if($house_usage['count'] > 0) {
        $error_msg = "Cannot delete house because it has {$house_usage['count']} booking requests.";
    } else {
        // Get image path to delete
        $db->query("SELECT image_path FROM houses WHERE id = :id");
        $db->bind(':id', $house_id);
        $house = $db->single();
        
        // Delete the house
        $db->query("DELETE FROM houses WHERE id = :id");
        $db->bind(':id', $house_id);
        
        if($db->execute()) {
            // Delete image file if it exists and is not the default
            if(!empty($house['image_path']) && file_exists(ROOT_PATH . '/' . $house['image_path']) && strpos($house['image_path'], 'house-default.jpg') === false) {
                unlink(ROOT_PATH . '/' . $house['image_path']);
            }
            
            $success_msg = "House deleted successfully.";
        } else {
            $error_msg = "Failed to delete house.";
        }
    }
}

// Get all categories for dropdown
$db->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $db->resultSet();

// Get all houses with category names
$db->query("SELECT h.*, c.name as category_name FROM houses h 
           LEFT JOIN categories c ON h.category_id = c.id 
           ORDER BY h.house_no ASC");
$houses = $db->resultSet();

// Get house for viewing if ID is provided
$view_house = null;
if(isset($_GET['view']) && !empty($_GET['view'])) {
    $view_id = (int)$_GET['view'];
    $db->query("SELECT h.*, c.name as category_name FROM houses h 
               LEFT JOIN categories c ON h.category_id = c.id 
               WHERE h.id = :id");
    $db->bind(':id', $view_id);
    $view_house = $db->single();
}

// Include header
include '../includes/header.php';
?>

<div class="wrapper d-flex">
    <!-- Sidebar -->
    <?php include '../includes/admin_sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="content flex-grow-1">
        <!-- Topbar -->
        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
            <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                <i class="fa fa-bars"></i>
            </button>
            
            <h1 class="h3 mb-0 text-gray-800">Houses Management</h1>
            
            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown no-arrow">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo $_SESSION['rental_user']['name']; ?></span>
                        <img class="img-profile rounded-circle" src="<?php echo BASE_URL; ?>/assets/img/admin-avatar.png" width="32">
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/profile.php">
                            <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                            Profile
                        </a>
                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/settings.php">
                            <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                            Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/logout.php">
                            <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                            Logout
                        </a>
                    </div>
                </li>
            </ul>
        </nav>
        
        <!-- Begin Page Content -->
        <div class="container-fluid">
            <!-- Page Heading -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Houses</h1>
                <button type="button" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#addHouseModal">
                    <i class="fas fa-plus fa-sm text-white-50"></i> Add New House
                </button>
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
            
            <!-- Houses List -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">All Houses</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>House No.</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($houses as $house): ?>
                                <tr>
                                    <td><?php echo $house['house_no']; ?></td>
                                    <td><?php echo $house['category_name']; ?></td>
                                    <td><?php echo get_setting('currency_symbol') . number_format($house['price'], 2); ?></td>
                                    <td>
                                        <?php if($house['status'] == 0): ?>
                                        <span class="badge badge-success">Available</span>
                                        <?php else: ?>
                                        <span class="badge badge-danger">Occupied</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="?view=<?php echo $house['id']; ?>" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?edit=<?php echo $house['id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete=<?php echo $house['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this house?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <?php if($view_house): ?>
            <!-- House Details -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">House Details: <?php echo $view_house['house_no']; ?></h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <img src="<?php echo BASE_URL . '/' . (!empty($view_house['image_path']) ? $view_house['image_path'] : 'assets/img/house-default.jpg'); ?>" class="img-fluid rounded" alt="House Image">
                        </div>
                        <div class="col-md-8">
                            <h4><?php echo $view_house['house_no']; ?></h4>
                            <p><strong>Category:</strong> <?php echo $view_house['category_name']; ?></p>
                            <p><strong>Price:</strong> <?php echo get_setting('currency_symbol') . number_format($view_house['price'], 2); ?> per month</p>
                            <p><strong>Status:</strong> 
                                <?php if($view_house['status'] == 0): ?>
                                <span class="badge badge-success">Available</span>
                                <?php else: ?>
                                <span class="badge badge-danger">Occupied</span>
                                <?php endif; ?>
                            </p>
                            <p><strong>Description:</strong></p>
                            <p><?php echo $view_house['description']; ?></p>
                            
                            <div class="mt-3">
                                <a href="?edit=<?php echo $view_house['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-edit mr-1"></i>Edit House
                                </a>
                                <a href="?delete=<?php echo $view_house['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this house?');">
                                    <i class="fas fa-trash mr-1"></i>Delete House
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <!-- /.container-fluid -->
        
        <!-- Footer -->
        <footer class="sticky-footer bg-white">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>Copyright &copy; <?php echo get_setting('site_name'); ?> <?php echo date('Y'); ?></span>
                </div>
            </div>
        </footer>
        <!-- End of Footer -->
    </div>
</div>

<!-- Add House Modal -->
<div class="modal fade" id="addHouseModal" tabindex="-1" role="dialog" aria-labelledby="addHouseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addHouseModalLabel">Add New House</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="house_no">House Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="house_no" name="house_no" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="category_id">Category <span class="text-danger">*</span></label>
                                <select class="form-control" id="category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="price">Price (per month) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><?php echo get_setting('currency_symbol'); ?></span>
                                    </div>
                                    <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">Status <span class="text-danger">*</span></label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="0">Available</option>
                                    <option value="1">Occupied</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">House Image</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="image" name="image" accept="image/*">
                            <label class="custom-file-label" for="image">Choose file</label>
                        </div>
                        <small class="form-text text-muted">Upload an image of the house (optional). Maximum file size: 2MB. Supported formats: JPG, JPEG, PNG.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_house" class="btn btn-primary">Add House</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DataTables JavaScript -->
<script src="https://cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.22/js/dataTables.bootstrap4.min.js"></script>

<script>
$(document).ready(function() {
    $('#dataTable').DataTable();
    
    // Display file name when a file is selected
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
    });
});
</script>

<?php include '../includes/footer.php'; ?>