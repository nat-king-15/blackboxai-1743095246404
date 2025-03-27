<?php
/**
 * House Rental Management System
 * Tenant Profile
 */

// Include initialization file
require_once '../includes/init.php';

// Check if user is logged in as tenant
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'tenant') {
    redirect(BASE_URL . '/tenant/login.php');
}

// Page title
$page_title = 'My Profile';

// Get tenant details
$tenant_id = $_SESSION['rental_user']['id'];
$db->query("SELECT * FROM tenant_accounts WHERE id = :id");
$db->bind(':id', $tenant_id);
$tenant = $db->single();

// Process form submission for profile update
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    // Sanitize input
    $firstname = htmlspecialchars(trim($_POST['firstname']));
    $middlename = htmlspecialchars(trim($_POST['middlename']));
    $lastname = htmlspecialchars(trim($_POST['lastname']));
    $email = htmlspecialchars(trim($_POST['email']));
    $contact = htmlspecialchars(trim($_POST['contact']));
    
    // Validate required fields
    if(empty($firstname) || empty($lastname) || empty($email) || empty($contact)) {
        $error_msg = 'Please fill all required fields';
    } else {
        // Check if email is already used by another tenant
        $db->query("SELECT id FROM tenant_accounts WHERE email = :email AND id != :id");
        $db->bind(':email', $email);
        $db->bind(':id', $tenant_id);
        
        if($db->rowCount() > 0) {
            $error_msg = 'Email is already in use by another account';
        } else {
            // Update tenant profile
            $db->query("UPDATE tenant_accounts SET 
                        firstname = :firstname, 
                        middlename = :middlename, 
                        lastname = :lastname, 
                        email = :email, 
                        contact = :contact 
                        WHERE id = :id");
            
            // Bind values
            $db->bind(':firstname', $firstname);
            $db->bind(':middlename', $middlename);
            $db->bind(':lastname', $lastname);
            $db->bind(':email', $email);
            $db->bind(':contact', $contact);
            $db->bind(':id', $tenant_id);
            
            // Execute
            if($db->execute()) {
                // Update session data
                $_SESSION['rental_user']['firstname'] = $firstname;
                $_SESSION['rental_user']['lastname'] = $lastname;
                $_SESSION['rental_user']['email'] = $email;
                $_SESSION['rental_user']['contact'] = $contact;
                
                $success_msg = 'Profile updated successfully';
                
                // Refresh tenant data
                $db->query("SELECT * FROM tenant_accounts WHERE id = :id");
                $db->bind(':id', $tenant_id);
                $tenant = $db->single();
            } else {
                $error_msg = 'Failed to update profile. Please try again.';
            }
        }
    }
}

// Process form submission for password change
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
        $db->query("SELECT password FROM tenant_accounts WHERE id = :id");
        $db->bind(':id', $tenant_id);
        $result = $db->single();
        
        if(md5($current_password) != $result['password']) {
            $pwd_error_msg = 'Current password is incorrect';
        } else {
            // Update password
            $db->query("UPDATE tenant_accounts SET password = :password WHERE id = :id");
            $db->bind(':password', md5($new_password));
            $db->bind(':id', $tenant_id);
            
            // Execute
            if($db->execute()) {
                $pwd_success_msg = 'Password changed successfully';
            } else {
                $pwd_error_msg = 'Failed to change password. Please try again.';
            }
        }
    }
}

// Include header and navbar
include '../includes/header.php';
include '../includes/navbar.php';
?>

<!-- Profile Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 mb-4">
                <!-- Profile Sidebar -->
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Account Settings</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="#profile-info" class="list-group-item list-group-item-action active">
                            <i class="fas fa-user mr-2"></i>Profile Information
                        </a>
                        <a href="#change-password" class="list-group-item list-group-item-action">
                            <i class="fas fa-key mr-2"></i>Change Password
                        </a>
                        <a href="<?php echo BASE_URL; ?>/tenant/dashboard.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-tachometer-alt mr-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-9">
                <!-- Profile Information -->
                <div class="card shadow mb-4" id="profile-info">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Profile Information</h6>
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
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="firstname">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo $tenant['firstname']; ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="middlename">Middle Name</label>
                                    <input type="text" class="form-control" id="middlename" name="middlename" value="<?php echo $tenant['middlename']; ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="lastname">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="lastname" name="lastname" value="<?php echo $tenant['lastname']; ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo $tenant['email']; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="contact">Contact Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="contact" name="contact" value="<?php echo $tenant['contact']; ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="username">Username</label>
                                    <input type="text" class="form-control" id="username" value="<?php echo $tenant['username']; ?>" readonly>
                                    <small class="text-muted">Username cannot be changed</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="date_created">Account Created</label>
                                    <input type="text" class="form-control" id="date_created" value="<?php echo date('F d, Y', strtotime($tenant['date_created'])); ?>" readonly>
                                </div>
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>Save Changes
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Change Password -->
                <div class="card shadow" id="change-password">
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
                                <label for="current_password">Current Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    </div>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="current_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">New Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                                    </div>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted">Password must be at least 6 characters long</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                                    </div>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-key mr-2"></i>Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

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
    
    // Smooth scroll to sections
    document.querySelectorAll('.list-group-item').forEach(item => {
        item.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            if(href.startsWith('#')) {
                e.preventDefault();
                const targetElement = document.querySelector(href);
                
                if(targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 20,
                        behavior: 'smooth'
                    });
                    
                    // Update active class
                    document.querySelectorAll('.list-group-item').forEach(el => {
                        el.classList.remove('active');
                    });
                    this.classList.add('active');
                }
            }
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
