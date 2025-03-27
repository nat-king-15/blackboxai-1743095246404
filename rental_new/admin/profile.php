<?php
/**
 * House Rental Management System
 * Admin Profile
 */

// Include initialization file
require_once '../includes/init.php';

// Check if user is logged in as admin
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    redirect(BASE_URL . '/admin/login.php');
}

// Page title
$page_title = 'Admin Profile';

// Initialize database
$db = new Database();

// Get admin details
$admin_id = $_SESSION['rental_user']['id'];
$db->query("SELECT * FROM users WHERE id = :id");
$db->bind(':id', $admin_id);
$admin = $db->single();

// Handle form submissions
$success_msg = '';
$error_msg = '';

// Process profile update
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    // Sanitize input
    $name = htmlspecialchars(trim($_POST['name']));
    $username = htmlspecialchars(trim($_POST['username']));
    
    // Validate required fields
    if(empty($name) || empty($username)) {
        $error_msg = 'Please fill all required fields';
    } else {
        // Check if username is already taken by another user
        $db->query("SELECT id FROM users WHERE username = :username AND id != :id");
        $db->bind(':username', $username);
        $db->bind(':id', $admin_id);
        
        if($db->rowCount() > 0) {
            $error_msg = 'Username is already taken by another user';
        } else {
            // Update profile
            $db->query("UPDATE users SET name = :name, username = :username WHERE id = :id");
            $db->bind(':name', $name);
            $db->bind(':username', $username);
            $db->bind(':id', $admin_id);
            
            if($db->execute()) {
                // Update session data
                $_SESSION['rental_user']['name'] = $name;
                $_SESSION['rental_user']['username'] = $username;
                
                $success_msg = 'Profile updated successfully';
                
                // Refresh admin data
                $db->query("SELECT * FROM users WHERE id = :id");
                $db->bind(':id', $admin_id);
                $admin = $db->single();
            } else {
                $error_msg = 'Failed to update profile';
            }
        }
    }
}

// Process password change
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    // Sanitize input
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate required fields
    if(empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $pwd_error_msg = 'Please fill all password fields';
    } elseif($new_password != $confirm_password) {
        $pwd_error_msg = 'New password and confirm password do not match';
    } elseif(strlen($new_password) < 6) {
        $pwd_error_msg = 'Password must be at least 6 characters long';
    } else {
        // Verify current password
        $db->query("SELECT password FROM users WHERE id = :id");
        $db->bind(':id', $admin_id);
        $result = $db->single();
        
        if(md5($current_password) != $result['password']) {
            $pwd_error_msg = 'Current password is incorrect';
        } else {
            // Update password
            $db->query("UPDATE users SET password = :password WHERE id = :id");
            $db->bind(':password', md5($new_password));
            $db->bind(':id', $admin_id);
            
            if($db->execute()) {
                $pwd_success_msg = 'Password changed successfully';
            } else {
                $pwd_error_msg = 'Failed to change password';
            }
        }
    }
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
            
            <h1 class="h3 mb-0 text-gray-800">Admin Profile</h1>
            
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
                <h1 class="h3 mb-0 text-gray-800">My Profile</h1>
                <a href="<?php echo BASE_URL; ?>/admin/index.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                    <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Dashboard
                </a>
            </div>
            
            <div class="row">
                <div class="col-lg-4">
                    <!-- Profile Card -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Profile Information</h6>
                        </div>
                        <div class="card-body text-center">
                            <img src="<?php echo BASE_URL; ?>/assets/img/admin-avatar.png" alt="Admin Avatar" class="img-fluid rounded-circle mb-3" style="width: 150px;">
                            <h4 class="card-title"><?php echo $admin['name']; ?></h4>
                            <p class="card-text">
                                <span class="badge badge-primary">Administrator</span>
                            </p>
                            <ul class="list-group list-group-flush mt-3">
                                <li class="list-group-item text-left">
                                    <strong><i class="fas fa-user mr-2"></i>Username:</strong> <?php echo $admin['username']; ?>
                                </li>
                                <li class="list-group-item text-left">
                                    <strong><i class="fas fa-shield-alt mr-2"></i>Role:</strong> 
                                    <?php echo $admin['type'] == 1 ? 'Administrator' : 'Staff'; ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-8">
                    <!-- Update Profile -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Update Profile</h6>
                        </div>
                        <div class="card-body">
                            <?php if(isset($error_msg)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error_msg; ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(isset($success_msg)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $success_msg; ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <?php endif; ?>
                            
                            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                                <div class="form-group">
                                    <label for="name">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo $admin['name']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo $admin['username']; ?>" required>
                                </div>
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> Update Profile
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Change Password -->
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Change Password</h6>
                        </div>
                        <div class="card-body">
                            <?php if(isset($pwd_error_msg)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $pwd_error_msg; ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(isset($pwd_success_msg)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $pwd_success_msg; ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <?php endif; ?>
                            
                            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                                <div class="form-group">
                                    <label for="current_password">Current Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="current_password">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="new_password">New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Password must be at least 6 characters long</small>
                                </div>
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="fas fa-key mr-1"></i> Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const toggleButtons = document.querySelectorAll('.toggle-password');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                passwordInput.type = 'password';
                this.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
