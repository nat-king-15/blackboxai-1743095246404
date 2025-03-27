<?php
/**
 * House Rental Management System
 * Admin Login
 */

// Include initialization file
require_once '../includes/init.php';

// Page title
$page_title = 'Admin Login';

// Check if already logged in
if(isset($_SESSION['user_type'])) {
    if($_SESSION['user_type'] == 'admin') {
        redirect(BASE_URL . '/admin/index.php');
    } else {
        redirect(BASE_URL . '/tenant/dashboard.php');
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
        $login_result = $user->adminLogin($username, $password);
        
        if($login_result) {
            // If remember me is checked, set cookie
            if($remember) {
                setcookie('admin_username', $username, time() + (86400 * 30), '/'); // 30 days
            }
            
            // Redirect to dashboard
            redirect(BASE_URL . '/admin/index.php');
        } else {
            $error_msg = $user->getError();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | <?php echo get_setting('site_name'); ?></title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>/assets/img/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    
    <style>
        body {
            background-color: #f8f9fc;
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 400px;
            width: 100%;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        .login-header {
            background: linear-gradient(to right, #4e73df, #224abe);
        }
        .login-footer {
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-10 col-lg-8">
                    <div class="card login-card border-0">
                        <div class="card-header login-header text-white text-center py-4">
                            <h2 class="mb-0"><i class="fas fa-user-shield mr-2"></i>Admin Login</h2>
                            <p class="mb-0 mt-2"><?php echo get_setting('site_name'); ?></p>
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
                                        <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" value="<?php echo isset($_COOKIE['admin_username']) ? $_COOKIE['admin_username'] : ''; ?>" required>
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
                                        <input type="checkbox" class="custom-control-input" id="remember" name="remember" <?php echo isset($_COOKIE['admin_username']) ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="remember">Remember me</label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block btn-lg">
                                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                                </button>
                            </form>
                            
                            <div class="text-center mt-4">
                                <a href="<?php echo BASE_URL; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-home mr-1"></i>Back to Home
                                </a>
                            </div>
                        </div>
                        <div class="card-footer login-footer text-center py-3">
                            <p class="text-muted mb-0">
                                &copy; <?php echo date('Y'); ?> <?php echo get_setting('site_name'); ?>. All rights reserved.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
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
</body>
</html>
