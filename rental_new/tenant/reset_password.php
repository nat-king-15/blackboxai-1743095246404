<?php
/**
 * House Rental Management System
 * Tenant Reset Password
 */

// Include initialization file
require_once '../includes/init.php';

// Page title
$page_title = 'Reset Password';

// Check if already logged in
if(isset($_SESSION['user_type'])) {
    if($_SESSION['user_type'] == 'tenant') {
        redirect(BASE_URL . '/tenant/dashboard.php');
    } else {
        redirect(BASE_URL . '/admin/index.php');
    }
}

// Check if token is provided
if(!isset($_GET['token']) || empty($_GET['token'])) {
    redirect(BASE_URL . '/tenant/forgot_password.php');
}

$token = $_GET['token'];
$token_valid = false;
$tenant_id = null;

// Verify token
$db->query("SELECT id, firstname, lastname FROM tenant_accounts WHERE reset_token = :token AND reset_expiry > NOW()");
$db->bind(':token', $token);
$tenant = $db->single();

if($tenant) {
    $token_valid = true;
    $tenant_id = $tenant['id'];
}

// Process form submission
if($_SERVER['REQUEST_METHOD'] == 'POST' && $token_valid) {
    // Sanitize input
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate required fields
    if(empty($new_password) || empty($confirm_password)) {
        $error_msg = 'Please fill all required fields';
    } elseif($new_password != $confirm_password) {
        $error_msg = 'Passwords do not match';
    } elseif(strlen($new_password) < 6) {
        $error_msg = 'Password must be at least 6 characters long';
    } else {
        // Update password and clear token
        $db->query("UPDATE tenant_accounts SET password = :password, reset_token = NULL, reset_expiry = NULL WHERE id = :id");
        $db->bind(':password', md5($new_password)); // Using MD5 for compatibility
        $db->bind(':id', $tenant_id);
        
        if($db->execute()) {
            $success_msg = "Your password has been reset successfully. You can now login with your new password.";
            $token_valid = false; // Prevent form resubmission
        } else {
            $error_msg = "An error occurred. Please try again later.";
        }
    }
}

// Include header and navbar
include '../includes/header.php';
include '../includes/navbar.php';
?>

<!-- Reset Password Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-md-6 offset-md-3 col-lg-4 offset-lg-4">
                <div class="card shadow-lg">
                    <div class="card-header bg-primary text-white text-center py-3">
                        <h3 class="mb-0">Reset Password</h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if(!$token_valid && !isset($success_msg)): ?>
                        <div class="alert alert-danger" role="alert">
                            <h5 class="alert-heading">Invalid or Expired Token</h5>
                            <p>The password reset link is invalid or has expired. Please request a new password reset link.</p>
                            <hr>
                            <a href="<?php echo BASE_URL; ?>/tenant/forgot_password.php" class="btn btn-danger">
                                <i class="fas fa-redo mr-2"></i>Request New Link
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(isset($error_msg)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error_msg; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(isset($success_msg)): ?>
                        <div class="alert alert-success" role="alert">
                            <h5 class="alert-heading">Success!</h5>
                            <p><?php echo $success_msg; ?></p>
                            <hr>
                            <a href="<?php echo BASE_URL; ?>/tenant/login.php" class="btn btn-success">
                                <i class="fas fa-sign-in-alt mr-2"></i>Go to Login
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if($token_valid): ?>
                        <p class="text-muted mb-4">Please enter your new password below.</p>
                        
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>?token=<?php echo $token; ?>" method="post">
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                                    </div>
                                    <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Enter new password" required>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted">Password must be at least 6 characters long</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                                    </div>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block btn-lg">
                                <i class="fas fa-save mr-2"></i>Reset Password
                            </button>
                        </form>
                        <?php endif; ?>
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
});
</script>

<?php include '../includes/footer.php'; ?>
