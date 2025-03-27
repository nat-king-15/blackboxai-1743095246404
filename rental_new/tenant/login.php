<?php
/**
 * House Rental Management System
 * Tenant Login
 */

// Include initialization file
require_once '../includes/init.php';

// Page title
$page_title = 'Tenant Login';

// Check if already logged in
if(isset($_SESSION['user_type'])) {
    if($_SESSION['user_type'] == 'tenant') {
        redirect(BASE_URL . '/tenant/dashboard.php');
    } else {
        redirect(BASE_URL . '/admin/index.php');
    }
}

// Process form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input
    $username = htmlspecialchars(trim($_POST['username']));
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validate required fields
    if(empty($username) || empty($password)) {
        $error_msg = 'Please enter both username and password';
    } else {
        // Create User object and attempt login
        $user = new User();
        $login_result = $user->tenantLogin($username, $password);
        
        if($login_result) {
            // If remember me is checked, set cookie
            if($remember) {
                setcookie('tenant_username', $username, time() + (86400 * 30), '/'); // 30 days
            }
            
            // Redirect to dashboard
            redirect(BASE_URL . '/tenant/dashboard.php');
        } else {
            $error_msg = $user->getError();
        }
    }
}
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<!-- Login Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-md-6 offset-md-3 col-lg-4 offset-lg-4">
                <div class="card shadow-lg">
                    <div class="card-header bg-primary text-white text-center py-3">
                        <h3 class="mb-0">Tenant Login</h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if(isset($error_msg)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error_msg; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php endif; ?>
                        
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    </div>
                                    <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" value="<?php echo isset($_COOKIE['tenant_username']) ? $_COOKIE['tenant_username'] : ''; ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    </div>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="remember" name="remember" <?php echo isset($_COOKIE['tenant_username']) ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="remember">Remember me</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block btn-lg">
                                <i class="fas fa-sign-in-alt mr-2"></i>Login
                            </button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p>Don't have an account? <a href="<?php echo BASE_URL; ?>/tenant/register.php">Register here</a></p>
                            <p><a href="<?php echo BASE_URL; ?>/tenant/forgot_password.php">Forgot Password?</a></p>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-body bg-light">
                        <h5 class="card-title">Looking for admin login?</h5>
                        <p class="card-text">If you are an administrator, please use the admin login page.</p>
                        <a href="<?php echo BASE_URL; ?>/admin/login.php" class="btn btn-outline-primary">
                            <i class="fas fa-user-shield mr-2"></i>Admin Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const toggleButton = document.querySelector('.toggle-password');
    
    if(toggleButton) {
        toggleButton.addEventListener('click', function() {
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
    }
});
</script>

<?php include '../includes/footer.php'; ?> 